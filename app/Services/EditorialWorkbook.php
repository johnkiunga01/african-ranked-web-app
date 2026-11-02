<?php

namespace App\Services;

use App\Support\Catalog;
use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/** Bounded, read-only XLSX intake. No macros, formulas, external fetches or HTML execution. */
class EditorialWorkbook
{
    public function read(string $path, string $filename): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->fail('Choose a valid .xlsx workbook. Legacy .xls and macro-enabled files are not supported.');
        }
        try {
            $size = 0;
            if ($zip->numFiles > 500) {
                $this->fail('This workbook contains too many archive entries.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $size += $stat['size'];
                if ($size > 20000000 || preg_match('~vbaProject|externalLinks|(?:^|/)\.\.(?:/|$)~i', $stat['name'])) {
                    $this->fail('Use a workbook under 20 MB expanded, without macros or external workbook links.');
                }
            }
            $shared = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                foreach ($this->xml($zip, 'xl/sharedStrings.xml')->query('//*[local-name()="si"]') as $node) {
                    $shared[] = $node->textContent;
                }
            }
            $relations = [];
            foreach ($this->xml($zip, 'xl/_rels/workbook.xml.rels')->query('//*[local-name()="Relationship"]') as $node) {
                if ($node->getAttribute('TargetMode') !== 'External') {
                    $relations[$node->getAttribute('Id')] = $node->getAttribute('Target');
                }
            }
            $sheets = [];
            foreach ($this->xml($zip, 'xl/workbook.xml')->query('//*[local-name()="sheet"]') as $node) {
                $name = $node->getAttribute('name');
                if (! in_array($name, ['Article Ideas', 'Draft Articles', 'Structure Templates'], true)) {
                    continue;
                }
                $target = $relations[$node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')] ?? '';
                if (! preg_match('~^/?(?:xl/)?worksheets/[a-zA-Z0-9_-]+\.xml$~D', $target)) {
                    $this->fail('An unsupported worksheet reference was found.');
                }
                $entry = str_starts_with($target, '/') ? ltrim($target, '/') : (str_starts_with($target, 'xl/') ? $target : 'xl/'.$target);
                $sheets[$name] = $this->rows($this->xml($zip, $entry), $shared, $name === 'Structure Templates');
            }
            $ideas = $sheets['Article Ideas'] ?? [];
            $headerRow = array_key_first($ideas);
            $headers = $headerRow !== null ? $ideas[$headerRow] : null;
            unset($ideas[$headerRow]);
            $required = ['ID', 'Wave', 'Category', 'Template', 'CMS type', 'Working title', 'Slug', 'Primary keyword', 'Target body words', 'Key sources to cite', 'Data risk', 'Pillar', 'Internal links (IDs)', 'Recommended structure (H2 order)'];
            if (! $headers || array_diff($required, $headers)) {
                $this->fail('The Article Ideas sheet must retain the master guide headers.');
            }
            $drafts = $this->records($sheets['Draft Articles'] ?? []);
            $drafts = collect($drafts)->keyBy('ID');
            $categories = app(Catalog::class)->categories()->pluck('slug', 'name');
            $seen = [];
            $slugs = [];
            $records = [];
            foreach ($ideas as $rowNumber => $row) {
                $fields = [];
                foreach ($headers as $column => $header) {
                    $fields[$header] = trim($row[$column] ?? '');
                }
                if (! filled($fields['ID'])) {
                    continue;
                }
                $id = $fields['ID'];
                $slug = $fields['Slug'];
                if (! preg_match('/^AR-\d{3,5}$/D', $id) || isset($seen[$id]) || isset($slugs[$slug]) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 180) {
                    $this->fail('Plan IDs and slugs must be valid and unique. Check row '.$rowNumber.'.');
                }
                if (! in_array($fields['Wave'], ['1', '2', '3'], true) || ! isset($categories[$fields['Category']]) || mb_strlen($fields['Working title']) > 180 || ! filled($fields['Working title']) || ! filled($fields['Primary keyword']) || mb_strlen($fields['Primary keyword']) > 180) {
                    $this->fail('Check the wave, category, title and keyword for '.$id.'.');
                }
                $seen[$id] = true;
                $slugs[$slug] = true;
                $draft = $drafts->get($id, []);
                $records[] = ['plan_id' => $id, 'slug' => $slug, 'wave' => (int) $fields['Wave'], 'title' => $fields['Working title'], 'category_slug' => $categories[$fields['Category']], 'keyword' => $fields['Primary keyword'],
                    'plan' => ['file' => mb_substr(basename($filename), 0, 180), 'sheet' => 'Article Ideas', 'row' => $rowNumber, 'fields' => $fields,
                        'team_draft' => $draft['Draft article'] ?? '', 'team_notes' => $draft['Notes'] ?? '', 'team_status' => $draft['Draft status'] ?? '',
                        'templates' => $sheets['Structure Templates'] ?? []]];
            }
            if (! $records || count($records) > 250) {
                $this->fail('Import between one and 250 briefs at a time.');
            }

            return ['hash' => hash_file('sha256', $path), 'records' => $records, 'drafts' => count(array_filter($records, fn ($r) => filled($r['plan']['team_draft'])))];
        } finally {
            $zip->close();
        }
    }

    private function records(array $rows): array
    {
        $headerRow = array_key_first($rows);
        $headers = $headerRow !== null ? $rows[$headerRow] : [];
        unset($rows[$headerRow]);
        $records = [];
        foreach ($rows as $row) {
            $record = [];
            foreach ($headers as $column => $header) {
                $record[$header] = $row[$column] ?? '';
            }
            $records[] = $record;
        }

        return $records;
    }

    private function rows(DOMXPath $xml, array $shared, bool $allowCachedTotals = false): array
    {
        $rows = [];
        foreach ($xml->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            $number = (int) $row->getAttribute('r');
            if ($number > 300 || count($rows) > 250) {
                $this->fail('Worksheet exceeds the 250-record intake limit.');
            }
            $values = [];
            foreach ($row->childNodes as $cell) {
                if ($cell->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $ref = $cell->getAttribute('r');
                if (! preg_match('/^([A-Z]{1,2})\d+$/D', $ref, $match)) {
                    $this->fail('Invalid worksheet cell reference.');
                }
                $column = 0;
                foreach (str_split($match[1]) as $letter) {
                    $column = $column * 26 + ord($letter) - 64;
                }
                if ($column > 40 || (! $allowCachedTotals && $xml->query('./*[local-name()="f"]', $cell)->length)) {
                    $this->fail('Plan, draft and template cells must contain literal text, not formulas.');
                }
                $value = $cell->getAttribute('t') === 'inlineStr' ? $xml->evaluate('string(./*[local-name()="is"])', $cell) : $xml->evaluate('string(./*[local-name()="v"])', $cell);
                if ($cell->getAttribute('t') === 's') {
                    $value = $shared[(int) $value] ?? '';
                }
                if (mb_strlen($value) > 150000) {
                    $this->fail('A cell is too long for a single article draft.');
                }
                $values[$column] = $value;
            }
            $rows[$number] = $values;
        }

        return $rows;
    }

    private function xml(ZipArchive $zip, string $entry): DOMXPath
    {
        $source = $zip->getFromName($entry);
        if ($source === false || stripos($source, '<!DOCTYPE') !== false || stripos($source, '<!ENTITY') !== false) {
            $this->fail('Workbook XML is missing or contains unsupported entity declarations.');
        }
        $document = new DOMDocument;
        $prior = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($source, LIBXML_NONET | LIBXML_COMPACT)) {
                $this->fail('Workbook XML could not be read.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prior);
        }

        return new DOMXPath($document);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['workbook' => $message]);
    }
}
