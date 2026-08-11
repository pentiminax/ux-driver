export const docsNav = [
  {
    title: 'Installation',
    slug: 'getting-started/installation',
    section: 'Start here',
  },
  {
    title: 'First tour',
    slug: 'getting-started/first-tour',
    section: 'Start here',
  },
  {
    title: 'Hints',
    slug: 'getting-started/hints',
    section: 'Start here',
  },
  {
    title: 'Authoring modes',
    slug: 'guides/authoring-modes',
    section: 'Guides',
  },
  {
    title: 'Control and persistence',
    slug: 'guides/control-and-persistence',
    section: 'Guides',
  },
  {
    title: 'Events',
    slug: 'guides/events',
    section: 'Guides',
  },
  {
    title: 'Styling and localization',
    slug: 'guides/styling-and-localization',
    section: 'Guides',
  },
  {
    title: 'Dynamic targets',
    slug: 'guides/dynamic-targets',
    section: 'Guides',
  },
  {
    title: 'Security',
    slug: 'guides/security',
    section: 'Guides',
  },
  {
    title: 'Tour',
    slug: 'reference/tour',
    section: 'Reference',
  },
  {
    title: 'Step',
    slug: 'reference/step',
    section: 'Reference',
  },
  {
    title: 'Hints',
    slug: 'reference/hints',
    section: 'Reference',
  },
  {
    title: 'Stimulus',
    slug: 'reference/stimulus',
    section: 'Reference',
  },
  {
    title: 'Compatibility',
    slug: 'reference/compatibility',
    section: 'Reference',
  },
]

export const docsNavSections = Array.from(new Set(docsNav.map((item) => item.section))).map((section) => ({
  section,
  items: docsNav.filter((item) => item.section === section),
}))

export function withBase(path = '') {
  const base = import.meta.env.BASE_URL
  return `${base}${path}`.replace(/\/{2,}/g, '/')
}
