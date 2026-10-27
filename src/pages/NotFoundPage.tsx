import { Compass } from 'lucide-react';

interface NotFoundPageProps {
  onNavigate: (path: string) => void;
}

export function NotFoundPage({ onNavigate }: NotFoundPageProps) {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-700">
        <Compass className="h-8 w-8" />
      </div>
      <h1 className="mt-6 text-3xl font-bold text-stone-900">Page not found</h1>
      <p className="mt-2 max-w-md text-stone-600">
        The ranking you&apos;re looking for may have moved. Let&apos;s get you back on track.
      </p>
      <button
        onClick={() => onNavigate('/')}
        className="mt-6 rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 transition hover:bg-amber-400"
      >
        Back to home
      </button>
    </div>
  );
}
