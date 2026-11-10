<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\EditorialWorkflow;
use App\Services\PublicationQuality;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Stateless MCP transport over the existing narrow editorial policies. */
class EditorialMcpController extends Controller
{
    public function __invoke(Request $request)
    {
        $message = $request->validate(['jsonrpc' => 'required|in:2.0', 'id' => 'nullable', 'method' => 'required|string', 'params' => 'sometimes|array']);
        $id = $message['id'] ?? null;
        $method = $message['method'];
        if (str_starts_with($method, 'notifications/')) {
            return response('', 202);
        }
        $reply = fn ($result) => response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        if ($method === 'initialize') {
            return $reply(['protocolVersion' => '2025-03-26', 'capabilities' => ['tools' => (object) []], 'serverInfo' => ['name' => 'african-ranked-editorial', 'version' => '1.0.0']]);
        }
        if ($method === 'ping') {
            return $reply((object) []);
        }
        $tools = [
            ['name' => 'list_drafts', 'description' => 'Read up to three fresh eligible unpublished CMS drafts.', 'inputSchema' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 3]], 'additionalProperties' => false], 'annotations' => ['readOnlyHint' => true, 'openWorldHint' => false]],
            ['name' => 'get_article', 'description' => 'Read an unpublished article, evidence, current revision and review history.', 'inputSchema' => ['type' => 'object', 'properties' => ['article_id' => ['type' => 'integer', 'minimum' => 1]], 'required' => ['article_id'], 'additionalProperties' => false], 'annotations' => ['readOnlyHint' => true, 'openWorldHint' => false]],
        ];
        foreach (['save_draft' => 'Save a new private revision of an existing unpublished article. Requires expected_revision_id and complete manuscript/evidence payload. Never approves or publishes.', 'submit_for_review' => 'Submit a complete unpublished draft for human review. Requires expected_revision_id; all editorial quality gates apply. Never approves or publishes.'] as $name => $description) {
            $tools[] = ['name' => $name, 'description' => $description, 'inputSchema' => ['type' => 'object', 'properties' => ['article_id' => ['type' => 'integer', 'minimum' => 1], 'payload' => ['type' => 'object', 'properties' => ['expected_revision_id' => ['type' => 'integer', 'minimum' => 1]], 'required' => ['expected_revision_id'], 'additionalProperties' => true]], 'required' => ['article_id', 'payload'], 'additionalProperties' => false], 'annotations' => ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false, 'openWorldHint' => false]];
        }
        if ($method === 'tools/list') {
            return $reply(['tools' => $tools]);
        }
        if ($method !== 'tools/call') {
            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => 'Method not found']]);
        }
        $name = $request->input('params.name');
        $args = $request->input('params.arguments', []);
        if (! is_array($args) || ! in_array($name, ['list_drafts', 'get_article', 'save_draft', 'submit_for_review'], true)) {
            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32602, 'message' => 'Unknown tool or invalid arguments']]);
        }
        $input = Request::create('/', 'POST', in_array($name, ['save_draft', 'submit_for_review'], true) ? ($args['payload'] ?? []) : $args);
        $input->attributes->set('editorial_actor', $request->attributes->get('editorial_actor'));
        try {
            $controller = app(EditorialIntegrationController::class);
            $result = match ($name) {
                'list_drafts' => $controller->drafts($input),
                'get_article' => $controller->show($input, Article::findOrFail($args['article_id'] ?? 0), app(PublicationQuality::class)),
                'save_draft' => $controller->save($input, Article::findOrFail($args['article_id'] ?? 0), app(EditorialWorkflow::class)),
                'submit_for_review' => $controller->submit($input, Article::findOrFail($args['article_id'] ?? 0), app(EditorialWorkflow::class), app(PublicationQuality::class)),
            };

            return $reply(['content' => [['type' => 'text', 'text' => $result->getContent()]], 'isError' => false]);
        } catch (ValidationException $exception) {
            return $reply(['content' => [['type' => 'text', 'text' => json_encode($exception->errors())]], 'isError' => true]);
        }
    }
}
