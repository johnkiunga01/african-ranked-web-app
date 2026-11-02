// Synthetic content for the guarded disposable browser database only.
export function publicationBody(prefix = '') {
    return prefix + '\n\n' + 'Synthetic explanation exercises editorial controls in a disposable test database. '.repeat(150) + '\n\n' + [1,2,3].map(i => `[Synthetic source ${i}](https://source.example.test/${i})`).join('\n\n');
}
export const publicationFaqs = [1,2,3].map(i => `Synthetic question ${i}?\nThis is disposable test content.`).join('\n\n');

export async function publicationEvidence(page) {
    const date = new Date().toISOString().slice(0, 10);
    await page.getByLabel('Direct answer (40–60 words)', { exact: true }).fill('Synthetic answer for a disposable browser fixture. '.repeat(7));
    await page.getByLabel('Last verified', { exact: true }).fill(date);
    await page.getByLabel('Dated key facts (four to eight)', { exact: true }).fill([1,2,3,4].map(i => `Synthetic fact ${i} | https://source.example.test/${i} | ${date}`).join('\n'));
    await page.getByLabel('Cited sources (five or more)', { exact: true }).fill([1,2,3,4,5].map(i => `Synthetic source ${i} | Fixture publisher | https://source.example.test/${i} | ${date}`).join('\n'));
    await page.getByLabel('Hero image rights record', { exact: true }).fill('Synthetic browser fixture, not an actual media-rights assertion.');
}
