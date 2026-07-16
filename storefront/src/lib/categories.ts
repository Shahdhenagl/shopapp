import type { Category } from '@/types';

// GET /categories returns a flat, sort_order'd list where parent_id is the
// parent's slug (null = a top-level department) — clients build the tree from
// those references, exactly as the Flutter app does.

export function departments(all: Category[]): Category[] {
  return all.filter((c) => !c.parent_id);
}

export function childrenOf(all: Category[], id: string): Category[] {
  return all.filter((c) => c.parent_id === id);
}

/**
 * A category plus every category beneath it. Used to scope a rail or a browse
 * page to a whole department. Guards against a parent_id cycle.
 */
export function subtreeIds(all: Category[], id: string): string[] {
  const out: string[] = [];
  const queue = [id];
  const seen = new Set<string>();

  while (queue.length > 0) {
    const current = queue.shift()!;
    if (seen.has(current)) continue;
    seen.add(current);
    out.push(current);
    queue.push(...childrenOf(all, current).map((c) => c.id));
  }

  return out;
}
