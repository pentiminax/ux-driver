import { getCollection } from 'astro:content'

/**
 * The directory a page lives in is its section. Everything a section needs —
 * its label, its blurb, its rank in the sidebar — is declared here and nowhere
 * else, so adding a page is only ever adding a file.
 */
export const sections = [
  {
    id: 'getting-started',
    title: 'Start here',
    description: 'Install UX Driver and ship a first Tour and a first Hint.',
  },
  {
    id: 'guides',
    title: 'Guides',
    description: 'Author, control, style, and secure Tours, Highlights, and Hints.',
  },
  {
    id: 'reference',
    title: 'Reference',
    description: 'Every option, action, event, and supported version range.',
  },
] as const

export type SectionId = (typeof sections)[number]['id']

export interface NavItem {
  slug: string
  title: string
  navTitle: string
  description: string
  order: number
  section: (typeof sections)[number]
}

export interface NavSection {
  section: (typeof sections)[number]
  items: NavItem[]
}

function sectionOf(slug: string) {
  const id = slug.split('/')[0]
  const section = sections.find((candidate) => candidate.id === id)

  if (!section) {
    throw new Error(`Page "${slug}" is not in a known section directory (${sections.map((s) => s.id).join(', ')}).`)
  }

  return section
}

/** Flat reading order — drives the pager. `order` in the frontmatter is the rank. */
export async function getDocsNav(): Promise<NavItem[]> {
  const entries = await getCollection('docs')

  return entries
    .map((entry) => ({
      slug: entry.id,
      title: entry.data.title,
      navTitle: entry.data.navTitle ?? entry.data.title,
      description: entry.data.description,
      order: entry.data.order ?? Number.MAX_SAFE_INTEGER,
      section: sectionOf(entry.id),
    }))
    .sort((a, b) => a.order - b.order || a.slug.localeCompare(b.slug))
}

/** Same pages, grouped for the sidebar. Sections keep their declared order. */
export async function getDocsNavSections(): Promise<NavSection[]> {
  const nav = await getDocsNav()

  return sections
    .map((section) => ({ section, items: nav.filter((item) => item.section === section) }))
    .filter((group) => group.items.length > 0)
}

export function withBase(path = '') {
  const base = import.meta.env.BASE_URL
  return `${base}${path}`.replace(/\/{2,}/g, '/')
}
