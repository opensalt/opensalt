export interface CrosswalkJob {
  jobId: string;
  status: 'queued' | 'running' | 'completed' | 'partial' | 'failed' | 'cancelled';
  threshold: number;
  exactMatchThreshold: number;
  originFramework: FrameworkRef;
  destinationFramework: FrameworkRef;
  crosswalkFramework: FrameworkRef;
  progress: {
    total: number;
    processed: number;
    matched: number;
    exactMatchItems: number;
    relatedItems: number;
    skipped: number;
    failed: number;
  };
  startedAt: string | null;
  completedAt: string | null;
  error: string | null;
}

export interface FrameworkRef {
  id: number;
  title: string;
}

export type CrosswalkAssociationType = 'exactMatchOf' | 'isRelatedTo';

export type CrosswalkSubtype = 'exact' | 'related' | 'broaderThan' | 'narrowerThan';

export type CrosswalkStatus = 'pending' | 'approved' | 'rejected' | 'modified';

export interface CrosswalkAssociationExtensions {
  'crosswalk:confidence': number;
  'crosswalk:status': CrosswalkStatus;
  'crosswalk:subtype': CrosswalkSubtype;
  'crosswalk:jobId': string;
}

export interface CrosswalkReviewFilters {
  search: string;
  confidenceMin: number;
  confidenceMax: number;
  status: 'all' | CrosswalkStatus;
  associationType: 'all' | CrosswalkAssociationType;
}

export interface CrosswalkEstimateResult {
  originFrameworkId: number;
  destinationFrameworkId: number;
  threshold: number;
  originItemsWithEmbeddings: number;
  originItemsWithoutEmbeddings: number;
  destinationItemsWithEmbeddings: number;
  note: string;
}

export interface CrosswalkCreateRequest {
  originId: number;
  destinationId: number;
  crosswalkId: number;
  threshold: number;
  exactMatchThreshold: number;
}
