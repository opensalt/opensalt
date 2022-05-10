<?php

namespace App\Security;

class Permission
{
    final public const COMMENT_ADD = 'comment';
    final public const COMMENT_VIEW = 'comment_view';
    final public const COMMENT_UPDATE = 'comment_update';
    final public const COMMENT_DELETE = 'comment_delete';

    final public const FRAMEWORK_CREATE = 'create';
    final public const FRAMEWORK_LIST = 'list';
    final public const FRAMEWORK_VIEW = 'view';
    final public const FRAMEWORK_EDIT = 'edit';
    final public const FRAMEWORK_DELETE = 'delete';
    final public const FRAMEWORK_CREATE_SUBJECT = 'lsdoc';
    final public const FRAMEWORK_EDIT_ALL_SUBJECT = 'all_frameworks';

    final public const ITEM_EDIT = 'edit';
    final public const ITEM_ADD_TO = 'add-standard-to';

    final public const ASSOCIATION_ADD_TO = 'add-association-to';
    final public const ASSOCIATION_CREATE = 'create';
    final public const ASSOCIATION_CREATE_SUBJECT = 'lsassociation';
    final public const ASSOCIATION_EDIT = 'edit';

    final public const ADDITIONAL_FIELDS_MANAGE = 'manage';
    final public const ADDITIONAL_FIELDS_MANAGE_SUBJECT = 'additional_fields';

    final public const MANAGE_MIRRORS = 'manage';
    final public const MANAGE_MIRRORS_SUBJECT = 'mirrors';

    final public const MANAGE_ORGANIZATIONS = 'manage';
    final public const MANAGE_ORGANIZATIONS_SUBJECT = 'organizations';

    final public const MANAGE_USERS = 'manage';
    final public const MANAGE_USERS_SUBJECT = 'users';
    final public const MANAGE_ALL_USERS_SUBJECT = 'all_users';

    final public const MANAGE_EDITORS = 'manage_editors';

    final public const MANAGE_SYSTEM_LOGS = 'manage';
    final public const MANAGE_SYSTEM_LOGS_SUBJECT = 'system_logs';

    final public const FEATURE_CHECK = 'feature';
    final public const FEATURE_SUBJECT_DEV_ENV = 'dev_env';
}
