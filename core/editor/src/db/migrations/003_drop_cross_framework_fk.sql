-- Migration: Drop cross-framework foreign key constraints
-- These constraints were incorrectly added to schema.sql and prevent
-- cross-framework associations from being inserted (their origin/destination
-- items reference items from other documents that don't exist locally).
-- Also clears cached data so frameworks are re-imported without FK violations.

ALTER TABLE associations DROP CONSTRAINT IF EXISTS fk_associations_origin;
ALTER TABLE associations DROP CONSTRAINT IF EXISTS fk_associations_destination;
ALTER TABLE item_association_edges DROP CONSTRAINT IF EXISTS fk_edges_item;
ALTER TABLE item_association_edges DROP CONSTRAINT IF EXISTS fk_edges_association;

-- Clear cached framework data so it gets re-imported with the FKs removed.
-- Without this, the stale cache would skip re-import and associations would
-- remain missing.
DELETE FROM frameworks;
DELETE FROM documents;
DELETE FROM items;
DELETE FROM associations;
DELETE FROM item_association_edges;
DELETE FROM item_search;
