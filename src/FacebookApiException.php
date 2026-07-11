<?php

declare(strict_types=1);

/**
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/bsd BSD
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Service\Facebook;

use Horde\Exception\HordeRuntimeException;

/**
 * Root exception for the Facebook Graph API client library.
 *
 * All library-thrown exceptions extend from this class so callers can
 * catch every failure mode of a Facebook operation without listing every
 * concrete exception type. Wire-level errors carrying a decoded Meta
 * error envelope are represented by the more specific
 * Horde\Service\Facebook\Graph\GraphErrorException subclass; see that
 * class for the value-object accessor over the error taxonomy.
 *
 * Extends HordeRuntimeException per the framework-wide convention that
 * modern Horde libraries emit Horde-branded exceptions rather than bare
 * SPL types, so consumers get uniform logging, tracing, and framework
 * integration across the ecosystem.
 */
class FacebookApiException extends HordeRuntimeException {}
