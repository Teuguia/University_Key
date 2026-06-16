/**
 * Logos de marques utilises dans les boutons sociaux et le footer.
 * Ils sont gardes en SVG local pour eviter une dependance a des images externes.
 */
export function BrandIcon({ name, className = 'h-5 w-5' }) {
  const icons = {
    facebook: (
      <svg aria-hidden="true" className={className} viewBox="0 0 32 32">
        <circle cx="16" cy="16" r="16" fill="#1877F2" />
        <path
          d="M20.6 17.1h-3.1V28h-4.6V17.1h-2.2v-3.9h2.2v-2.5c0-1.8.9-4.7 4.7-4.7h3.4v3.8h-2.5c-.4 0-1 .2-1 1.1v2.3h3.5l-.4 3.9Z"
          fill="#fff"
        />
      </svg>
    ),
    google: (
      <svg aria-hidden="true" className={className} viewBox="0 0 32 32">
        <path
          d="M29.4 16.3c0-1-.1-1.9-.3-2.8H16v5.3h7.5a6.4 6.4 0 0 1-2.8 4.2v3.5h4.5c2.6-2.4 4.2-5.9 4.2-10.2Z"
          fill="#4285F4"
        />
        <path
          d="M16 30c3.8 0 7-1.3 9.2-3.5L20.7 23c-1.2.8-2.8 1.3-4.7 1.3-3.7 0-6.8-2.5-7.9-5.8H3.5v3.6A14 14 0 0 0 16 30Z"
          fill="#34A853"
        />
        <path
          d="M8.1 18.5a8.4 8.4 0 0 1 0-5.4V9.5H3.5a14 14 0 0 0 0 12.6l4.6-3.6Z"
          fill="#FBBC05"
        />
        <path
          d="M16 7.7c2 0 3.9.7 5.3 2.1l4-4A13.3 13.3 0 0 0 16 2 14 14 0 0 0 3.5 9.5l4.6 3.6c1.1-3.3 4.2-5.4 7.9-5.4Z"
          fill="#EA4335"
        />
      </svg>
    ),
    instagram: (
      <svg aria-hidden="true" className={className} viewBox="0 0 32 32">
        <defs>
          <radialGradient id="instagram-gradient" cx="30%" cy="105%" r="115%">
            <stop offset="0%" stopColor="#FEDA75" />
            <stop offset="25%" stopColor="#FA7E1E" />
            <stop offset="50%" stopColor="#D62976" />
            <stop offset="75%" stopColor="#962FBF" />
            <stop offset="100%" stopColor="#4F5BD5" />
          </radialGradient>
        </defs>
        <circle cx="16" cy="16" r="16" fill="url(#instagram-gradient)" />
        <rect x="8.5" y="8.5" width="15" height="15" rx="4.5" fill="none" stroke="#fff" strokeWidth="2.2" />
        <circle cx="16" cy="16" r="3.8" fill="none" stroke="#fff" strokeWidth="2.2" />
        <circle cx="21.2" cy="10.8" r="1.3" fill="#fff" />
      </svg>
    ),
    twitter: (
      <svg aria-hidden="true" className={className} viewBox="0 0 32 32">
        <circle cx="16" cy="16" r="16" fill="#1DA1F2" />
        <path
          d="M25.6 11.1v.6c0 6.1-4.6 13.1-13.1 13.1-2.6 0-5-.8-7.1-2.1h1.1c2.1 0 4.1-.7 5.7-1.9-2 0-3.6-1.3-4.2-3.1.3.1.6.1.9.1.4 0 .8-.1 1.2-.2-2-.4-3.6-2.2-3.6-4.3v-.1c.6.3 1.3.5 2 .6a4.5 4.5 0 0 1-1.4-6c2.2 2.7 5.5 4.5 9.2 4.7a4.5 4.5 0 0 1 7.7-4.1c1-.2 1.9-.6 2.8-1.1-.3 1-1 1.8-1.9 2.3.9-.1 1.7-.3 2.5-.7-.6.8-1.2 1.5-1.8 1.7Z"
          fill="#fff"
        />
      </svg>
    ),
  }

  return icons[name] ?? null
}
