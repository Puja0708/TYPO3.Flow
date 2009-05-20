<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Locale
 * @version $Id$
 */

/**
 * Represents a locale
 *
 * Objects of this kind conveniently represent locales usually described by
 * locale identifiers such as de_DE, en_Latin_US etc. The locale identifiers
 * used are defined in the Unicode Technical Standard #35 (Unicode Locale
 * Data Markup Language).
 *
 * Using this class asserts the validity of the used locale and provides you
 * with some useful methods for getting more information about it.
 *
 * @package FLOW3
 * @subpackage Locale
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see http://www.unicode.org/reports/tr35/
 * @scope prototype
 */
class Locale {

	/**
	 * Simplified pattern which maches (most) locale identifiers
	 *
	 * @see http://rfc.net/rfc4646.html
	 */
	const PATTERN_MATCH_LOCALEIDENTIFIER = '/^(?P<language>[a-zA-Z]{2,3})(?:[-_](?P<script>[a-zA-Z]{4}))?(?:[-_](?P<region>[a-zA-Z]{2}|[0-9]{3})){0,1}(?:[-_](?P<variant>(?:[a-zA-Z0-9]{5,8})|(?:[0-9][a-zA-Z0-9]{3})))?(?:[-_].+)*$/';

	/**
	 * The language identifier - a BCP47, ISO 639-3 or 639-5 code
	 * Like the standard says, we use "mul" to label multilanguage content
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://en.wikipedia.org/wiki/ISO_639
	 */
	protected $language = 'en';

	/**
	 * The script identifier - an ISO 15924 code according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://unicode.org/iso15924/iso15924-codes.html
	 */
	protected $script = 'Latn';

	/**
	 * The region identifier - an ISO 3166-1-alpha-2 code or a UN M.49 three digit code
	 * Note: We use "ZZ" for "unknown region" or "global"
	 *
	 * @var string
	 * @see http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm
	 * @see http://en.wikipedia.org/wiki/UN_M.49
	 */
	protected $region = 'EN';


	/**
	 * The optional variant identifier - one of the registered registered variants according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 */
	protected $variant = '';

	/**
	 * Constructs this locale object
	 *
	 * @param string $localeIdentifier A valid locale identifier according to UTS#35
	 * @throws F3_FLOW3_Locale_Exception_InvalidLocaleIdentifier if the locale identifier is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function __construct($localeIdentifier) {
		if (!is_string($localeIdentifier)) throw new \InvalidArgumentException('A locale identifier must be of type string, ' . gettype($localeIdentifier) . ' given.', 1221216120);
		if (preg_match(self::PATTERN_MATCH_LOCALEIDENTIFIER, $localeIdentifier, $matches) === 0) throw new \F3\FLOW3\Locale\Exception\InvalidLocaleIdentifier('"' . $localeIdentifier . '" is not a valid locale identifier.', 1221137814);

		$this->language = strtolower($matches['language']);
		if (isset($matches['script'])) $this->script = ucfirst(strtolower($matches['script']));
		if (isset($matches['region'])) $this->region = strtoupper($matches['region']);
	}

	/**
	 * Returns the language defined in this locale
	 *
	 * @return string The language identifier
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Returns the script defined in this locale
	 *
	 * @return string The script identifier
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function getScript() {
		return $this->script;
	}

	/**
	 * Returns the region defined in this locale
	 *
	 * @return string The region identifier
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function getRegion() {
		return $this->region;
	}

}
?>