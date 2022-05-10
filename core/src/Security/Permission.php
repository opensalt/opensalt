<?php

namespace App\Security;

class Permission
{
    final public const COMMENT_ADD = 'comment';
    final public const COMMENT_VIEW = 'comment_view';
    final public const COMMENT_UPDATE = 'comment_update';
    final public const COMMENT_DELETE = 'comment_delete';

    final public const FRAMEWORK_CREATE = 'framework_create';
    final public const FRAMEWORK_LIST = 'list';
    final public const FRAMEWORK_VIEW = 'view';
    final public const FRAMEWORK_EDIT = 'edit';
    final public const FRAMEWORK_EDIT_ALL = 'framework_edit_all';
    final public const FRAMEWORK_DELETE = 'delete';

    final public const ITEM_EDIT = 'edit';
    final public const ITEM_ADD_TO = 'add-standard-to';

    final public const ASSOCIATION_ADD_TO = 'add-association-to';
    final public const ASSOCIATION_CREATE = 'create';
    final public const ASSOCIATION_CREATE_SUBJECT = 'lsassociation';
    final public const ASSOCIATION_EDIT = 'edit';

    final public const ADDITIONAL_FIELDS_MANAGE = 'manage_additional_fields';

    final public const MANAGE_MIRRORS = 'manage_mirrors';

    final public const MANAGE_ORGANIZATIONS = 'manage_organizations';

    final public const MANAGE_USERS = 'manage_users';
    final public const MANAGE_THIS_USER = 'manage_user';
    final public const MANAGE_ALL_USERS = 'manage_all_users';

    final public const MANAGE_EDITORS = 'manage_editors';

    final public const MANAGE_SYSTEM_LOGS = 'manage_system_logs';

    final public const FEATURE_DEV_ENV_CHECK = 'feature_dev_env';
}
