<?php

declare(strict_types=1);

namespace App\Security;

class Feature
{
    final public const string COMMENTS = 'comments';
    final public const string EDITOR_LOCAL_FRAMEWORK_DB = 'editor_local_framework_db';
    final public const string EDITOR_LOCAL_ASSOCIATION_QUERIES = 'editor_local_association_queries';
    final public const string EDITOR_LOCAL_TREE_QUERIES = 'editor_local_tree_queries';
    final public const string EDITOR_LOCAL_SIMILARITY_SEARCH = 'editor_local_similarity_search';
}
