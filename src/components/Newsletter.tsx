import { useState } from 'react';
import { motion } from 'framer-motion';
import { Mail, Check } from 'lucide-react';
import type { Region } from '@/types';

interface NewsletterProps {
  region: Region;
}

export function Newsletter({ region }: NewsletterProps) {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!email) return;
    setSubmitted(true);
  }

  return (
    <section className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950 px-6 py-12 sm:px-12 sm:py-16">
      <div className="absolute -right-12 -top-12 h-48 w-48 rounded-full bg-amber-600/20 blur-3xl" />
      <div className="absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-orange-700/20 blur-3xl" />

      <div className="relative mx-auto max-w-2xl text-center">
        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-500 text-stone-950">
          <Mail className="h-6 w-6" />
        </div>
        <h2 className="text-2xl font-bold text-white sm:text-3xl">
          {region.localizedTerms.newsletterTitle}
        </h2>
        <p className="mt-3 text-stone-300">{region.localizedTerms.newsletterSubtitle}</p>

        {submitted ? (
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="mt-6 flex items-center justify-center gap-2 rounded-xl bg-green-500/20 py-4 text-green-300"
          >
            <Check className="h-5 w-5" />
            <span className="font-medium">You&apos;re in! Check your inbox for confirmation.</span>
          </motion.div>
        ) : (
          <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="Enter your email"
              className="w-full max-w-sm rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder-stone-400 backdrop-blur focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30 sm:w-auto"
            />
            <button
              type="submit"
              className="rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 transition hover:bg-amber-400"
            >
              {region.localizedTerms.subscribe}
            </button>
          </form>
        )}
        <p className="mt-3 text-xs text-stone-400">
          We respect your privacy. Unsubscribe at any time.
        </p>
      </div>
    </section>
  );
}
