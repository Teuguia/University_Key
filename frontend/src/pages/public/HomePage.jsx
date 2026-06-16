const heroImage = '/images/hero-student.png'

// Donnees des etapes affichees dans le parcours "Comment ca marche ?".
const steps = [
  {
    title: 'Créez votre profil',
    text: "Renseignez vos informations et vos centres d'intérêt.",
    icon: 'user',
  },
  {
    title: "Passez le test d'orientation",
    text: 'Répondez à quelques questions pour mieux vous connaître.',
    icon: 'clipboard',
  },
  {
    title: 'Découvrez vos recommandations',
    text: 'Recevez des filières et écoles adaptées à votre profil.',
    icon: 'target',
  },
  {
    title: 'Construisez votre avenir',
    text: 'Explorez, comparez et choisissez en toute confiance.',
    icon: 'briefcase',
  },
]

// Indicateurs rapides mis en avant sous les etapes.
const impactStats = [
  { value: '150+', label: 'Établissements référencés', icon: 'building' },
  { value: '500+', label: 'Filières disponibles', icon: 'book' },
  { value: '100+', label: 'Conseillers certifiés', icon: 'users' },
  { value: '10 000+', label: 'Étudiants accompagnés', icon: 'graduation' },
]

// Cartes des filieres populaires affichees sur la landing page.
const programs = [
  { name: 'Médecine', icon: 'stethoscope' },
  { name: 'Génie Logiciel', icon: 'laptop' },
  { name: 'Comptabilité', icon: 'calculator' },
  { name: 'Droit', icon: 'scale' },
  { name: 'Agronomie', icon: 'leaf', accent: 'green' },
  { name: 'Kinésithérapie', icon: 'therapy' },
]

// Cartes des ecoles populaires avec une pastille de couleur pour differencier chaque etablissement.
const schools = [
  { name: 'Université de Yaoundé I', initials: 'UYI', color: 'bg-red-50 text-red-700' },
  { name: 'Université de Douala', initials: 'UD', color: 'bg-amber-50 text-amber-700' },
  { name: 'Université de Buea', initials: 'UB', color: 'bg-emerald-50 text-emerald-700' },
  { name: 'IAI Cameroun', initials: 'IAI', color: 'bg-green-50 text-green-700' },
  { name: 'Université des Montagnes', initials: 'UDM', color: 'bg-cyan-50 text-cyan-700' },
  { name: 'ISTAG', initials: 'IS', color: 'bg-rose-50 text-rose-700' },
]

// Composant interne pour reutiliser les icones SVG sans dependance externe.
function Icon({ name, className = 'h-8 w-8' }) {
  // Les proprietes communes gardent toutes les icones dans le meme style visuel.
  const common = {
    className,
    fill: 'none',
    stroke: 'currentColor',
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    strokeWidth: 2,
    viewBox: '0 0 24 24',
    'aria-hidden': true,
  }

  const icons = {
    book: (
      <svg {...common}>
        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z" />
        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 8H20" />
      </svg>
    ),
    briefcase: (
      <svg {...common}>
        <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1" />
        <path d="M4 7h16v12H4z" />
        <path d="M4 12h16" />
      </svg>
    ),
    building: (
      <svg {...common}>
        <path d="M3 21h18" />
        <path d="M5 21V9l7-5 7 5v12" />
        <path d="M9 21v-6h6v6" />
        <path d="M9 10h.01M12 10h.01M15 10h.01" />
      </svg>
    ),
    calculator: (
      <svg {...common}>
        <rect height="18" rx="2" width="14" x="5" y="3" />
        <path d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01" />
      </svg>
    ),
    clipboard: (
      <svg {...common}>
        <path d="M9 4h6l1 2h3v15H5V6h3l1-2Z" />
        <path d="M9 11h6M9 15h6M8 11l1 1 2-2" />
      </svg>
    ),
    graduation: (
      <svg {...common}>
        <path d="m3 8 9-4 9 4-9 4-9-4Z" />
        <path d="M7 10v4c3 2 7 2 10 0v-4" />
        <path d="M21 8v6" />
      </svg>
    ),
    laptop: (
      <svg {...common}>
        <path d="M5 5h14v10H5z" />
        <path d="M3 19h18" />
        <path d="m9 9-2 2 2 2M15 9l2 2-2 2" />
      </svg>
    ),
    leaf: (
      <svg {...common}>
        <path d="M20 4c-8 0-14 5-14 13 7 1 13-4 14-13Z" />
        <path d="M6 17c3-4 7-7 12-9" />
      </svg>
    ),
    scale: (
      <svg {...common}>
        <path d="M12 3v18M5 7h14" />
        <path d="M6 7 3 14h6L6 7ZM18 7l-3 7h6l-3-7Z" />
      </svg>
    ),
    stethoscope: (
      <svg {...common}>
        <path d="M6 3v5a4 4 0 0 0 8 0V3" />
        <path d="M10 16a5 5 0 0 0 10 0v-2" />
        <circle cx="20" cy="12" r="2" />
      </svg>
    ),
    target: (
      <svg {...common}>
        <circle cx="12" cy="12" r="8" />
        <circle cx="12" cy="12" r="4" />
        <path d="m15 9 4-4M17 5h2v2" />
      </svg>
    ),
    therapy: (
      <svg {...common}>
        <path d="M12 5a3 3 0 1 0 0.01 0Z" />
        <path d="M7 21c0-4 2-7 5-7s5 3 5 7" />
        <path d="M4 15c2-1 4-2 8-2s6 1 8 2" />
      </svg>
    ),
    user: (
      <svg {...common}>
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4 21a8 8 0 0 1 12-7" />
        <path d="M19 15v6M16 18h6" />
      </svg>
    ),
    users: (
      <svg {...common}>
        <path d="M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM16 21a8 8 0 0 0-16 0" />
        <path d="M17 11a3 3 0 1 0 0-6" />
        <path d="M24 21a7 7 0 0 0-7-7" />
      </svg>
    ),
  }

  return icons[name] ?? null
}

// Titre de section reutilise pour conserver le meme soulignement bleu partout.
function SectionTitle({ children }) {
  return (
    <div className="text-center">
      <h2 className="text-2xl font-black tracking-normal text-[#061d49] md:text-3xl">{children}</h2>
      <span className="mx-auto mt-3 block h-1 w-10 rounded-full bg-[#0b58bd]" aria-hidden="true" />
    </div>
  )
}

export function HomePage({ labels }) {
  return (
    <>
      {/* Hero: premiere impression avec promesse, boutons d'action et image etudiante. */}
      <section className="overflow-hidden border-b border-slate-200 bg-white" id="home">
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-12 pt-10 sm:px-6 md:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:pb-0">
          <div className="max-w-2xl">
            <h1 className="text-4xl font-black leading-tight tracking-normal text-[#061d49] sm:text-5xl lg:text-6xl">
              {labels.heroTitle}
            </h1>
            <p className="mt-6 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">
              {labels.heroText}
            </p>
            <div className="mt-8 flex flex-col gap-4 sm:flex-row">
              <a
                className="focus-ring inline-flex min-h-12 items-center justify-center gap-3 rounded-md bg-[#073f8f] px-6 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-[#052f6f]"
                href="#how-it-works"
              >
                {labels.primaryCta}
                <span aria-hidden="true">→</span>
              </a>
              <a
                className="focus-ring inline-flex min-h-12 items-center justify-center rounded-md border border-[#0b58bd] px-6 text-sm font-black text-[#073f8f] hover:bg-blue-50"
                href="#programs"
              >
                {labels.secondaryCta}
              </a>
            </div>
            <div className="mt-9 flex flex-wrap items-center gap-4">
              <div className="flex -space-x-2">
                {['A', 'B', 'C', 'D'].map((initial) => (
                  <span
                    className="grid h-8 w-8 place-items-center rounded-full border-2 border-white bg-gradient-to-br from-blue-100 to-emerald-100 text-xs font-black text-[#073f8f]"
                    key={initial}
                  >
                    {initial}
                  </span>
                ))}
              </div>
              <p className="text-sm text-slate-600">
                <span className="font-black text-[#073f8f]">10 000+</span> {labels.studentsHelped}
              </p>
            </div>
          </div>

          <div className="relative min-h-[420px] lg:min-h-[580px]">
            <div className="absolute right-0 top-0 h-full w-full rounded-[42%_58%_50%_50%/42%_40%_60%_58%] bg-blue-100" />
            <img
              alt="Étudiant souriant sur un campus"
              className="absolute inset-x-0 bottom-0 z-10 mx-auto h-[420px] w-[88%] rounded-[42%_58%_50%_50%/42%_40%_60%_58%] object-cover object-[50%_42%] shadow-2xl shadow-blue-950/10 md:h-[520px] lg:h-[580px]"
              src={heroImage}
            />
            <div className="absolute left-2 top-16 z-20 grid h-20 w-20 place-items-center rounded-full bg-white text-[#073f8f] shadow-xl shadow-blue-950/10">
              <Icon className="h-10 w-10" name="graduation" />
            </div>
            <div className="absolute bottom-28 left-0 z-20 grid h-20 w-20 place-items-center rounded-full bg-white text-[#073f8f] shadow-xl shadow-blue-950/10">
              <Icon className="h-10 w-10" name="building" />
            </div>
            <div className="absolute right-14 top-20 z-20 grid h-20 w-20 place-items-center rounded-full bg-white text-[#073f8f] shadow-xl shadow-blue-950/10">
              <Icon className="h-10 w-10" name="book" />
            </div>
            <span className="absolute left-0 top-48 h-36 w-36 rounded-full border-2 border-dashed border-blue-200" aria-hidden="true" />
            <span className="absolute right-3 top-8 h-44 w-44 rounded-full border-2 border-dashed border-blue-200" aria-hidden="true" />
          </div>
        </div>
      </section>

      {/* Fonctionnement: explique le parcours utilisateur en quatre etapes simples. */}
      <section className="bg-white px-4 py-10 sm:px-6 lg:px-8" id="how-it-works">
        <div className="mx-auto max-w-7xl">
          <SectionTitle>{labels.howTitle}</SectionTitle>
          <div className="mt-10 grid gap-8 md:grid-cols-4">
            {steps.map((step, index) => (
              <article className="relative text-center" key={step.title}>
                {index < steps.length - 1 && (
                  <span className="absolute left-[62%] top-10 hidden w-[76%] border-t border-dashed border-blue-200 md:block" aria-hidden="true" />
                )}
                <div className="relative mx-auto grid h-24 w-24 place-items-center rounded-full bg-gradient-to-br from-blue-50 to-slate-50 text-[#073f8f] shadow-sm">
                  <Icon className="h-10 w-10" name={step.icon} />
                </div>
                <span className="mx-auto -mt-3 grid h-7 w-7 place-items-center rounded-full bg-[#073f8f] text-xs font-black text-white">
                  {index + 1}
                </span>
                <h3 className="mt-3 text-base font-black text-[#061d49]">{step.title}</h3>
                <p className="mx-auto mt-4 max-w-[220px] text-sm leading-6 text-slate-500">{step.text}</p>
              </article>
            ))}
          </div>

          <div className="mt-10 grid gap-0 overflow-hidden rounded-lg border border-slate-100 bg-slate-50 shadow-sm md:grid-cols-4">
            {impactStats.map((stat) => (
              <div className="flex items-center gap-5 border-b border-slate-200 px-6 py-6 md:border-b-0 md:border-r last:md:border-r-0" key={stat.label}>
                <Icon className="h-10 w-10 shrink-0 text-[#073f8f]" name={stat.icon} />
                <div>
                  <p className="text-3xl font-black text-[#073f8f]">{stat.value}</p>
                  <p className="text-sm leading-5 text-slate-600">{stat.label}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Filieres populaires: cartes courtes pour guider rapidement l'exploration. */}
      <section className="bg-white px-4 py-8 sm:px-6 lg:px-8" id="programs">
        <div className="mx-auto max-w-7xl">
          <SectionTitle>{labels.sectionPrograms}</SectionTitle>
          <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            {programs.map((program) => (
              <article className="rounded-lg border border-slate-100 bg-white p-6 text-center shadow-lg shadow-slate-200/60" key={program.name}>
                <div className={`mx-auto grid h-12 w-12 place-items-center ${program.accent === 'green' ? 'text-[#2f9f43]' : 'text-[#073f8f]'}`}>
                  <Icon className="h-10 w-10" name={program.icon} />
                </div>
                <h3 className="mt-5 text-sm font-black text-[#061d49]">{program.name}</h3>
              </article>
            ))}
          </div>
          <div className="mt-6 text-center">
            <a className="focus-ring inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-black text-[#073f8f] hover:bg-blue-50" href="#programs">
              Voir toutes les filières <span aria-hidden="true">→</span>
            </a>
          </div>
        </div>
      </section>

      {/* Ecoles populaires: liste compacte des etablissements a mettre en avant. */}
      <section className="bg-white px-4 py-8 sm:px-6 lg:px-8" id="schools">
        <div className="mx-auto max-w-7xl">
          <div className="relative">
            <SectionTitle>{labels.sectionSchools}</SectionTitle>
            <div className="absolute right-0 top-0 hidden gap-3 md:flex">
              <button className="focus-ring grid h-9 w-9 place-items-center rounded-full border border-slate-200 text-[#073f8f]" type="button" aria-label="Écoles précédentes">
                ‹
              </button>
              <button className="focus-ring grid h-9 w-9 place-items-center rounded-full border border-slate-200 text-[#073f8f]" type="button" aria-label="Écoles suivantes">
                ›
              </button>
            </div>
          </div>
          <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            {schools.map((school) => (
              <article className="flex min-h-24 items-center gap-4 rounded-lg border border-slate-100 bg-white p-4 shadow-sm" key={school.name}>
                <span className={`grid h-12 w-12 shrink-0 place-items-center rounded-full text-xs font-black ${school.color}`}>
                  {school.initials}
                </span>
                <h3 className="text-sm font-black leading-5 text-[#061d49]">{school.name}</h3>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* CTA final: encourage la creation de compte apres la decouverte de la page. */}
      <section className="bg-white px-4 py-8 sm:px-6 lg:px-8" id="about">
        <div className="mx-auto max-w-7xl rounded-lg bg-gradient-to-r from-[#073071] to-[#074aa8] px-6 py-8 text-white shadow-xl shadow-blue-950/20 md:flex md:items-center md:justify-between md:px-14">
          <div>
            <h2 className="text-2xl font-black">{labels.finalCtaTitle}</h2>
            <p className="mt-3 text-sm text-blue-100">{labels.finalCtaText}</p>
          </div>
          <a
            className="focus-ring mt-6 inline-flex min-h-12 items-center justify-center gap-4 rounded-md bg-white px-6 text-sm font-black text-[#073f8f] hover:bg-blue-50 md:mt-0"
            href="#auth"
          >
            {labels.finalCtaButton}
            <span aria-hidden="true">→</span>
          </a>
        </div>
      </section>
    </>
  )
}
