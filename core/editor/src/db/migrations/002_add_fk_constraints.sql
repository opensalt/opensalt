-- Migration: Add foreign key constraints with ON DELETE CASCADE
-- Only same-document references are constrained.
-- Cross-framework references (associations -> items for origin/destination,
-- item_association_edges -> items/associations) are NOT constrained because
-- cross-framework items may reference items from other documents that don't
-- exist in the local items table.

-- items -> documents
ALTER TABLE items 
  ADD CONSTRAINT fk_items_document 
  FOREIGN KEY (document_id) 
  REFERENCES documents(document_id) 
  ON DELETE CASCADE;

-- associations -> documents
ALTER TABLE associations 
  ADD CONSTRAINT fk_associations_document 
  FOREIGN KEY (document_id) 
  REFERENCES documents(document_id) 
  ON DELETE CASCADE;

-- item_association_edges -> documents
ALTER TABLE item_association_edges 
  ADD CONSTRAINT fk_edges_document 
  FOREIGN KEY (source_document_id) 
  REFERENCES documents(document_id) 
  ON DELETE CASCADE;

-- item_search -> documents
ALTER TABLE item_search 
  ADD CONSTRAINT fk_search_document 
  FOREIGN KEY (document_id) 
  REFERENCES documents(document_id) 
  ON DELETE CASCADE;

-- item_search -> items
ALTER TABLE item_search 
  ADD CONSTRAINT fk_search_item 
  FOREIGN KEY (item_id) 
  REFERENCES items(item_id) 
  ON DELETE CASCADE;
