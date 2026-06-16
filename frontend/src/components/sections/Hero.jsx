/**
 * Presente la promesse produit et la recherche rapide mobile-first.
 */
export function Hero({ labels }) {
  return (
    <section id="home" className="border-b border-slate-200 bg-white">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.15fr_0.85fr] md:py-16 lg:px-8">
        <div className="flex flex-col justify-center">
          <p className="mb-3 text-sm font-black uppercase tracking-normal text-emerald-700">
            {labels.heroEyebrow}
          </p>
          <h1 className="max-w-3xl text-4xl font-black leading-tight tracking-normal text-slate-950 sm:text-5xl lg:text-6xl">
            {labels.heroTitle}
          </h1>
          <p className="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
            {labels.heroText}
          </p>

          <form className="mt-7 grid gap-3 sm:grid-cols-[1fr_auto]" role="search">
            <label className="sr-only" htmlFor="global-search">
              {labels.searchPlaceholder}
            </label>
            <input
              className="focus-ring min-h-12 rounded-md border border-slate-300 bg-white px-4 text-base text-slate-950 placeholder:text-slate-500"
              id="global-search"
              placeholder={labels.searchPlaceholder}
              type="search"
            />
            <button
              className="focus-ring min-h-12 rounded-md bg-emerald-700 px-5 text-base font-black text-white hover:bg-emerald-800"
              type="submit"
            >
              {labels.searchButton}
            </button>
          </form>

          <div className="mt-6 flex flex-col gap-3 sm:flex-row">
            <a
              className="focus-ring rounded-md bg-orange-500 px-5 py-3 text-center font-black text-white hover:bg-orange-600"
              href="#test"
            >
              {labels.primaryCta}
            </a>
            <a
              className="focus-ring rounded-md border border-slate-300 px-5 py-3 text-center font-black text-slate-900 hover:bg-slate-50"
              href="#schools"
            >
              {labels.secondaryCta}
            </a>
          </div>
        </div>

        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm">
          <div className="rounded-md bg-white p-4 shadow-sm">
            <p className="text-sm font-bold text-slate-500">Profil analyse</p>
            <div className="mt-4 space-y-3">
              {labels.stats.map((stat) => (
                <div
                  className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-3"
                  key={stat}
                >
                  <span className="font-bold text-slate-800">{stat}</span>
                  <span className="h-2 w-16 rounded-full bg-emerald-600" aria-hidden="true" />
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

