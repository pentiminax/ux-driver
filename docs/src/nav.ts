export const docsNav = [
  {
    title: 'Introduction',
    slug: 'getting-started/introduction',
    section: 'Getting started',
  },
]

export function withBase(path = '') {
  const base = import.meta.env.BASE_URL
  return `${base}${path}`.replace(/\/{2,}/g, '/')
}
