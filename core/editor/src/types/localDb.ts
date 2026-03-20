export interface LocalFrameworkCacheEntry<TData = unknown> {
  id: string;
  data: TData;
  cachedAt: number;
  lastChangeDateTime?: string | null;
  etag?: string | null;
  lastModified?: string | null;
}

export interface LocalRelatedDocument {
  identifier: string;
  uri?: string;
  title?: string;
  CFPackageURI?: string;
}

