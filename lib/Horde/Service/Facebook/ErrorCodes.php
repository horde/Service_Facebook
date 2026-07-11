<?php

/**
 * Error codes and descriptions for the Facebook API.
 *
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Facebook
 */
class Horde_Service_Facebook_ErrorCodes
{
    public const API_EC_SUCCESS = 0;

    /*
     * GENERAL ERRORS
     */
    public const API_EC_UNKNOWN = 1;
    public const API_EC_SERVICE = 2;
    public const API_EC_METHOD = 3;
    public const API_EC_TOO_MANY_CALLS = 4;
    public const API_EC_BAD_IP = 5;
    public const API_EC_HOST_API = 6;
    public const API_EC_HOST_UP = 7;
    public const API_EC_SECURE = 8;
    public const API_EC_RATE = 9;
    public const API_EC_PERMISSION_DENIED = 10;
    public const API_EC_DEPRECATED = 11;
    public const API_EC_VERSION = 12;

    /*
     * PARAMETER ERRORS
     */
    public const API_EC_PARAM = 100;
    public const API_EC_PARAM_API_KEY = 101;
    public const API_EC_PARAM_SESSION_KEY = 102;
    public const API_EC_PARAM_CALL_ID = 103;
    public const API_EC_PARAM_SIGNATURE = 104;
    public const API_EC_PARAM_TOO_MANY = 105;
    public const API_EC_PARAM_USER_ID = 110;
    public const API_EC_PARAM_USER_FIELD = 111;
    public const API_EC_PARAM_SOCIAL_FIELD = 112;
    public const API_EC_PARAM_EMAIL = 113;
    public const API_EC_PARAM_USER_ID_LIST = 114;
    public const API_EC_PARAM_ALBUM_ID = 120;
    public const API_EC_PARAM_PHOTO_ID = 121;
    public const API_EC_PARAM_FEED_PRIORITY = 130;
    public const API_EC_PARAM_CATEGORY = 140;
    public const API_EC_PARAM_SUBCATEGORY = 141;
    public const API_EC_PARAM_TITLE = 142;
    public const API_EC_PARAM_DESCRIPTION = 143;
    public const API_EC_PARAM_BAD_JSON = 144;
    public const API_EC_PARAM_BAD_EID = 150;
    public const API_EC_PARAM_UNKNOWN_CITY = 151;
    public const API_EC_PARAM_BAD_PAGE_TYPE = 152;

    /*
     * USER PERMISSIONS ERRORS
     */
    public const API_EC_PERMISSION = 200;
    public const API_EC_PERMISSION_USER = 210;
    public const API_EC_PERMISSION_ALBUM = 220;
    public const API_EC_PERMISSION_PHOTO = 221;
    public const API_EC_PERMISSION_MESSAGE = 230;
    public const API_EC_PERMISSION_OTHER_USER = 240;
    public const API_EC_PERMISSION_STATUS_UPDATE = 250;
    public const API_EC_PERMISSION_PHOTO_UPLOAD = 260;
    public const API_EC_PERMISSION_VIDEO_UPLOAD = 261;
    public const API_EC_PERMISSION_SMS = 270;
    public const API_EC_PERMISSION_CREATE_LISTING = 280;
    public const API_EC_PERMISSION_CREATE_NOTE = 281;
    public const API_EC_PERMISSION_SHARE_ITEM = 282;
    public const API_EC_PERMISSION_EVENT = 290;
    public const API_EC_PERMISSION_LARGE_FBML_TEMPLATE = 291;
    public const API_EC_PERMISSION_LIVEMESSAGE = 292;
    public const API_EC_PERMISSION_RSVP_EVENT = 299;

    /*
     * DATA EDIT ERRORS
     */
    public const API_EC_EDIT = 300;
    public const API_EC_EDIT_USER_DATA = 310;
    public const API_EC_EDIT_PHOTO = 320;
    public const API_EC_EDIT_ALBUM_SIZE = 321;
    public const API_EC_EDIT_PHOTO_TAG_SUBJECT = 322;
    public const API_EC_EDIT_PHOTO_TAG_PHOTO = 323;
    public const API_EC_EDIT_PHOTO_FILE = 324;
    public const API_EC_EDIT_PHOTO_PENDING_LIMIT = 325;
    public const API_EC_EDIT_PHOTO_TAG_LIMIT = 326;
    public const API_EC_EDIT_ALBUM_REORDER_PHOTO_NOT_IN_ALBUM = 327;
    public const API_EC_EDIT_ALBUM_REORDER_TOO_FEW_PHOTOS = 328;

    public const API_EC_MALFORMED_MARKUP = 329;
    public const API_EC_EDIT_MARKUP = 330;

    public const API_EC_EDIT_FEED_TOO_MANY_USER_CALLS = 340;
    public const API_EC_EDIT_FEED_TOO_MANY_USER_ACTION_CALLS = 341;
    public const API_EC_EDIT_FEED_TITLE_LINK = 342;
    public const API_EC_EDIT_FEED_TITLE_LENGTH = 343;
    public const API_EC_EDIT_FEED_TITLE_NAME = 344;
    public const API_EC_EDIT_FEED_TITLE_BLANK = 345;
    public const API_EC_EDIT_FEED_BODY_LENGTH = 346;
    public const API_EC_EDIT_FEED_PHOTO_SRC = 347;
    public const API_EC_EDIT_FEED_PHOTO_LINK = 348;

    public const API_EC_EDIT_VIDEO_SIZE = 350;
    public const API_EC_EDIT_VIDEO_INVALID_FILE = 351;
    public const API_EC_EDIT_VIDEO_INVALID_TYPE = 352;
    public const API_EC_EDIT_VIDEO_FILE = 353;

    public const API_EC_EDIT_FEED_TITLE_ARRAY = 360;
    public const API_EC_EDIT_FEED_TITLE_PARAMS = 361;
    public const API_EC_EDIT_FEED_BODY_ARRAY = 362;
    public const API_EC_EDIT_FEED_BODY_PARAMS = 363;
    public const API_EC_EDIT_FEED_PHOTO = 364;
    public const API_EC_EDIT_FEED_TEMPLATE = 365;
    public const API_EC_EDIT_FEED_TARGET = 366;
    public const API_EC_EDIT_FEED_MARKUP = 367;

    /**
     * SESSION ERRORS
     */
    public const API_EC_SESSION_TIMED_OUT = 450;
    public const API_EC_SESSION_METHOD = 451;
    public const API_EC_SESSION_INVALID = 452;
    public const API_EC_SESSION_REQUIRED = 453;
    public const API_EC_SESSION_REQUIRED_FOR_SECRET = 454;
    public const API_EC_SESSION_CANNOT_USE_SESSION_SECRET = 455;


    /**
     * FQL ERRORS
     */
    public const FQL_EC_UNKNOWN_ERROR = 600;
    public const FQL_EC_PARSER = 601; // backwards compatibility
    public const FQL_EC_PARSER_ERROR = 601;
    public const FQL_EC_UNKNOWN_FIELD = 602;
    public const FQL_EC_UNKNOWN_TABLE = 603;
    public const FQL_EC_NOT_INDEXABLE = 604; // backwards compatibility
    public const FQL_EC_NO_INDEX = 604;
    public const FQL_EC_UNKNOWN_FUNCTION = 605;
    public const FQL_EC_INVALID_PARAM = 606;
    public const FQL_EC_INVALID_FIELD = 607;
    public const FQL_EC_INVALID_SESSION = 608;
    public const FQL_EC_UNSUPPORTED_APP_TYPE = 609;
    public const FQL_EC_SESSION_SECRET_NOT_ALLOWED = 610;

    public const API_EC_REF_SET_FAILED = 700;

    /**
     * DATA STORE API ERRORS
     */
    public const API_EC_DATA_UNKNOWN_ERROR = 800;
    public const API_EC_DATA_INVALID_OPERATION = 801;
    public const API_EC_DATA_QUOTA_EXCEEDED = 802;
    public const API_EC_DATA_OBJECT_NOT_FOUND = 803;
    public const API_EC_DATA_OBJECT_ALREADY_EXISTS = 804;
    public const API_EC_DATA_DATABASE_ERROR = 805;
    public const API_EC_DATA_CREATE_TEMPLATE_ERROR = 806;
    public const API_EC_DATA_TEMPLATE_EXISTS_ERROR = 807;
    public const API_EC_DATA_TEMPLATE_HANDLE_TOO_LONG = 808;
    public const API_EC_DATA_TEMPLATE_HANDLE_ALREADY_IN_USE = 809;
    public const API_EC_DATA_TOO_MANY_TEMPLATE_BUNDLES = 810;
    public const API_EC_DATA_MALFORMED_ACTION_LINK = 811;
    public const API_EC_DATA_TEMPLATE_USES_RESERVED_TOKEN = 812;

    /*
     * APPLICATION INFO ERRORS
     */
    public const API_EC_NO_SUCH_APP = 900;

    /*
     * BATCH ERRORS
     */
    public const API_EC_BATCH_TOO_MANY_ITEMS = 950;
    public const API_EC_BATCH_ALREADY_STARTED = 951;
    public const API_EC_BATCH_NOT_STARTED = 952;
    public const API_EC_BATCH_METHOD_NOT_ALLOWED_IN_BATCH_MODE = 953;

    /*
     * EVENT API ERRORS
     */
    public const API_EC_EVENT_INVALID_TIME = 1000;

    /*
     * INFO BOX ERRORS
     */
    public const API_EC_INFO_NO_INFORMATION = 1050;
    public const API_EC_INFO_SET_FAILED = 1051;

    /*
     * LIVEMESSAGE API ERRORS
     */
    public const API_EC_LIVEMESSAGE_SEND_FAILED = 1100;
    public const API_EC_LIVEMESSAGE_EVENT_NAME_TOO_LONG = 1101;
    public const API_EC_LIVEMESSAGE_MESSAGE_TOO_LONG = 1102;

    /*
     * CONNECT SESSION ERRORS
     */
    public const API_EC_CONNECT_FEED_DISABLED = 1300;

    /*
     * Platform tag bundles errors
     */
    public const API_EC_TAG_BUNDLE_QUOTA = 1400;

    /*
     * SHARE
     */
    public const API_EC_SHARE_BAD_URL = 1500;

    /*
     * NOTES
     */
    public const API_EC_NOTE_CANNOT_MODIFY = 1600;

    /*
     * COMMENTS
     */
    public const API_EC_COMMENTS_UNKNOWN = 1700;
    public const API_EC_COMMENTS_POST_TOO_LONG = 1701;
    public const API_EC_COMMENTS_DB_DOWN = 1702;
    public const API_EC_COMMENTS_INVALID_XID = 1703;
    public const API_EC_COMMENTS_INVALID_UID = 1704;
    public const API_EC_COMMENTS_INVALID_POST = 1705;
}
