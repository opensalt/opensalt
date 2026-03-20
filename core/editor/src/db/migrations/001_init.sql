CREATE TABLE IF NOT EXISTS frameworks (
  id TEXT PRIMARY KEY,
  data JSON NOT NULL,
  cached_at BIGINT NOT NULL,
  last_change_datetime TEXT,
  etag TEXT,
  last_modified TEXT
);

CREATE TABLE IF NOT EXISTS related_frameworks (
  id TEXT PRIMARY KEY,
  data JSON NOT NULL,
  cached_at BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS documents (
  document_id TEXT PRIMARY KEY,
  uri TEXT,
  title TEXT,
  last_change_datetime TEXT,
  json_data JSON
);

CREATE TABLE IF NOT EXISTS items (
  item_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  uri TEXT,
  full_statement TEXT,
  abbreviated_statement TEXT,
  human_coding_scheme TEXT,
  item_type TEXT,
  last_change_datetime TEXT,
  json_data JSON
);

CREATE INDEX IF NOT EXISTS idx_items_document ON items(document_id);

CREATE TABLE IF NOT EXISTS associations (
  association_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  association_type TEXT NOT NULL,
  origin_item_id TEXT,
  destination_item_id TEXT,
  group_id TEXT,
  sequence_number INTEGER,
  json_data JSON
);

CREATE INDEX IF NOT EXISTS idx_associations_document ON associations(document_id);
CREATE INDEX IF NOT EXISTS idx_associations_origin ON associations(origin_item_id);
CREATE INDEX IF NOT EXISTS idx_associations_destination ON associations(destination_item_id);

CREATE TABLE IF NOT EXISTS item_association_edges (
  item_id TEXT NOT NULL,
  association_id TEXT NOT NULL,
  direction TEXT NOT NULL,
  association_type TEXT NOT NULL,
  other_item_id TEXT,
  group_id TEXT,
  source_document_id TEXT NOT NULL,
  PRIMARY KEY (item_id, association_id, direction)
);

CREATE INDEX IF NOT EXISTS idx_item_assoc_item ON item_association_edges(item_id);
CREATE INDEX IF NOT EXISTS idx_item_assoc_item_type ON item_association_edges(item_id, association_type);

CREATE TABLE IF NOT EXISTS item_search (
  item_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  search_text TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_item_search_doc ON item_search(document_id);
