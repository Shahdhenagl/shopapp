import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { favorites } from '@/api';
import { useAuth } from '@/store/auth';

/**
 * Favourites are a list of product ids server-side. Toggling returns the new
 * list, so we just seed the cache with it rather than refetching.
 */
export function useFavorites() {
  const authed = useAuth((s) => Boolean(s.token));
  const qc = useQueryClient();

  const query = useQuery({
    queryKey: ['favorites'],
    queryFn: () => favorites.ids(),
    enabled: authed,
  });

  const mutation = useMutation({
    mutationFn: (productId: string) => favorites.toggle(productId),
    onSuccess: (ids) => qc.setQueryData(['favorites'], ids),
  });

  const ids = query.data ?? [];

  return {
    ids,
    isFavorite: (productId: string) => ids.includes(productId),
    toggle: mutation.mutate,
    isToggling: mutation.isPending,
    enabled: authed,
  };
}
