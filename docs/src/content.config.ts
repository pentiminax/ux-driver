import { defineCollection } from 'astro:content'
import { glob } from 'astro/loaders'
import { z } from 'astro/zod'

const docs = defineCollection({
  loader: glob({ pattern: '**/*.{md,mdx}', base: './src/content/docs' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    /** Rank in the sidebar and the pager. Unranked pages sort last. */
    order: z.number().optional(),
    /** Shorter label for the sidebar when the page title is long. */
    navTitle: z.string().optional(),
  }),
})

export const collections = { docs }
