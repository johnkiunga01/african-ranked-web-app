import type { CmsArticle } from '@/lib/cms';

// New modules use semantic HTML; visual treatment awaits a design decision.
export function ArticleGovernance({ cms, section }: { cms?: CmsArticle; section: 'before' | 'body' | 'after' | 'end' }) {
  if (!cms) return null;
  if (section === 'before') return <>
    {cms.reviewer && <p>Reviewed by <a href={cms.reviewer.url}>{cms.reviewer.name}</a></p>}
    {cms.publishedAt && <p>Published <time dateTime={cms.publishedAt}>{cms.publishedAt}</time></p>}
    {cms.lastVerifiedAt && <p>Last verified <time dateTime={cms.lastVerifiedAt}>{cms.lastVerifiedAt}</time></p>}
    {cms.disclosure && <aside>{cms.disclosure}</aside>}
    {cms.answerBlock && <section><p>{cms.answerBlock}</p></section>}
    {!!cms.keyFacts?.length && <table><caption>Key facts</caption><tbody>{cms.keyFacts.map((f) => <tr key={f.label}><th scope="row">{f.label}</th><td>{f.value}</td><td><time>{f.verified_on}</time></td></tr>)}</tbody></table>}
  </>;
  if (section === 'body') return <>
    {!!cms.items?.length && <ol>{cms.items.map((i) => <li key={i.position} value={i.position}>
      <h2>{i.name}</h2><p>{i.blurb}</p>
      <dl>{Object.entries(i.scores).map(([criterion, score]) => <div key={criterion}><dt>{criterion}</dt><dd>{score}</dd></div>)}</dl>
      <output>{i.total}</output>
    </li>)}</ol>}
    {cms.bodyHtml && <div dangerouslySetInnerHTML={{ __html: cms.bodyHtml }} />}
  </>;
  if (section === 'after') return <>
    {cms.methodology && <section><h2>How we ranked</h2><p>{cms.methodology}</p></section>}
    {!!cms.sources?.length && <section><h2>Sources</h2><ul>{cms.sources.map((s) => <li key={s.url}><a href={s.url} rel="noopener noreferrer">{s.title}</a>{s.accessedAt && <time>{s.accessedAt}</time>}</li>)}</ul></section>}
  </>;
  return <>
    {!!cms.corrections?.length && <section><h2>Corrections</h2><ul>{cms.corrections.map((c, i) => <li key={i}><time>{c.correctedAt}</time> {c.summary}</li>)}</ul></section>}
  </>;
}
