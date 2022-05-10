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

    final public const ASSOCIATION_ADD_TO = 'add-association-to';
    final public const ASSOCIATION_CREATE = 'create';
    final public const ASSOCIATION_CREATE_SUBJECT = 'lsassociation';
    final public const ASSOCIATION_EDIT = 'edit';

    final public const MANAGE_SYSTEM_LOGS = 'manage_system_logs';

    final public const FEATURE_CHECK = 'feature';
    final public const FEATURE_SUBJECT_DEV_ENV = 'dev_env';
}
