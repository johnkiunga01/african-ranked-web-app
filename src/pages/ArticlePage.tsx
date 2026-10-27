import { motion } from 'framer-motion';
import { Clock, ArrowLeft, Share2, ChevronRight } from 'lucide-react';
import type { RegionCode, Article, Breadcrumb } from '@/types';
import { getRegion } from '@/data/regions';
import { getCategory } from '@/data/categories';
import { ArticleCard } from '@/components/ArticleCard';
import { Newsletter } from '@/components/Newsletter';
import { getArticlesByCategory } from '@/data/articles';
import { regionPath } from '@/utils/seo';

interface ArticlePageProps {
  region: RegionCode;
  article: Article;
  onNavigate: (path: string) => void;
  breadcrumbs: Breadcrumb[];
}

export function ArticlePage({ region, article, onNavigate, breadcrumbs }: ArticlePageProps) {
  const r = getRegion(region);
  const category = getCategory(article.category);
  const related = getArticlesByCategory(article.category)
    .filter((a) => a.slug !== article.slug)
    .slice(0, 3);

  function handleShare() {
    if (navigator.share) {
      navigator.share({ title: article.title, url: window.location.href }).catch(() => {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
    }
  }

  return (
    <div className="pb-16">
      {/* Breadcrumb */}
      <nav className="mx-auto max-w-3xl px-4 py-4 sm:px-6">
        <ol className="flex flex-wrap items-center gap-1.5 text-xs text-stone-500">
          {breadcrumbs.map((bc, i) => (
            <li key={bc.url} className="flex items-center gap-1.5">
              {i > 0 && <ChevronRight className="h-3 w-3" />}
              <button onClick={() => onNavigate(bc.url)} className="hover:text-amber-700">
                {bc.name}
              </button>
            </li>
          ))}
        </ol>
      </nav>

      {/* Article header */}
      <article className="mx-auto max-w-3xl px-4 sm:px-6">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          <button
            onClick={() => onNavigate(`${regionPath(region)}/categories/${article.category}`)}
            className="mb-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white"
            style={{ backgroundColor: category?.color }}
          >
            {category?.name}
          </button>

          <h1 className="text-3xl font-bold leading-tight text-stone-900 sm:text-4xl">
            {article.title}
          </h1>
          <p className="mt-4 text-lg leading-relaxed text-stone-600">{article.excerpt}</p>

          <div className="mt-6 flex items-center justify-between border-y border-stone-200 py-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-700 text-sm font-bold text-white">
                {article.author.split(' ').map((n) => n[0]).join('')}
              </div>
              <div>
                <div className="text-sm font-semibold text-stone-900">{article.author}</div>
                <div className="text-xs text-stone-500">{article.authorTitle}</div>
              </div>
            </div>
            <div className="flex items-center gap-4 text-xs text-stone-500">
              <span className="flex items-center gap-1">
                <Clock className="h-3.5 w-3.5" />
                {article.readTime} min
              </span>
              <button
                onClick={handleShare}
                className="flex items-center gap-1 font-medium text-amber-700 hover:text-amber-600"
              >
                <Share2 className="h-3.5 w-3.5" />
                Share
              </button>
            </div>
          </div>
        </motion.div>

        {/* Hero image */}
        <motion.img
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ duration: 0.6, delay: 0.1 }}
          src={article.image}
          alt={article.title}
          className="mt-8 aspect-[16/9] w-full rounded-2xl object-cover"
        />

        {/* Body */}
        <div className="mt-8 space-y-6">
          {article.body.map((block, i) => {
            switch (block.type) {
              case 'heading':
                return (
                  <motion.h2
                    key={i}
                    initial={{ opacity: 0, y: 10 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.3 }}
                    className="pt-4 text-2xl font-bold text-stone-900"
                  >
                    {block.text}
                  </motion.h2>
                );
              case 'paragraph':
                return (
                  <p key={i} className="text-base leading-relaxed text-stone-700">
                    {block.text}
                  </p>
                );
              case 'list':
                return (
                  <ul key={i} className="space-y-2 pl-5">
                    {block.items.map((item, j) => (
                      <li key={j} className="flex gap-3 text-base text-stone-700">
                        <span className="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-600" />
                        {item}
                      </li>
                    ))}
                  </ul>
                );
              case 'quote':
                return (
                  <blockquote
                    key={i}
                    className="border-l-4 border-amber-500 bg-amber-50/50 py-4 pl-6 pr-4"
                  >
                    <p className="text-lg italic text-stone-800">&ldquo;{block.text}&rdquo;</p>
                    <cite className="mt-2 block text-sm font-medium not-italic text-stone-600">
                      — {block.attribution}
                    </cite>
                  </blockquote>
                );
              default:
                return null;
            }
          })}
        </div>

        {/* FAQ */}
        {article.faqs && article.faqs.length > 0 && (
          <section className="mt-12 rounded-2xl border border-stone-200 bg-stone-50 p-6 sm:p-8">
            <h2 className="text-xl font-bold text-stone-900">Frequently asked questions</h2>
            <div className="mt-4 divide-y divide-stone-200">
              {article.faqs.map((faq, i) => (
                <div key={i} className="py-4">
                  <h3 className="font-semibold text-stone-900">{faq.question}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-stone-600">{faq.answer}</p>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Back link */}
        <div className="mt-10">
          <button
            onClick={() => onNavigate(`${regionPath(region)}/categories/${article.category}`)}
            className="inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-600"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to {category?.name}
          </button>
        </div>
      </article>

      {/* Related */}
      {related.length > 0 && (
        <section className="mx-auto mt-16 max-w-7xl px-4 sm:px-6">
          <h2 className="mb-6 text-2xl font-bold text-stone-900">More in {category?.name}</h2>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {related.map((a) => (
              <ArticleCard key={a.slug} article={a} region={region} onNavigate={onNavigate} />
            ))}
          </div>
        </section>
      )}

      {/* Newsletter */}
      <section className="mx-auto mt-16 max-w-7xl px-4 sm:px-6">
        <Newsletter region={r} />
      </section>
    </div>
  );
}
