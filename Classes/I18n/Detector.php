<?php
namespace TYPO3\FLOW3\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Detector class provides methods for automatic locale detection
 *
 * @scope singleton
 * @api
 */
class Detector {

	/**
	 * @var \TYPO3\FLOW3\I18n\Service
	 */
	protected $localizationService;

	/**
	 * A collection of Locale objects representing currently installed locales,
	 * in a hierarchical manner.
	 *
	 * @var \TYPO3\FLOW3\I18n\LocaleCollection
	 */
	protected $localeCollection;

	/**
	 * @param \TYPO3\FLOW3\I18n\Service $localizationService
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocalizationService(\TYPO3\FLOW3\I18n\Service $localizationService) {
		$this->localizationService = $localizationService;
	}

	/**
	 * @param \TYPO3\FLOW3\I18n\LocaleCollection $localeCollection
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function injectLocaleCollection(\TYPO3\FLOW3\I18n\LocaleCollection $localeCollection) {
		$this->localeCollection = $localeCollection;
	}

	/**
	 * Returns best-matching Locale object based on the Accept-Language header
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $acceptLanguageHeader The Accept-Language HTTP header
	 * @return \TYPO3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromHttpHeader($acceptLanguageHeader) {
		$acceptableLanguages = \TYPO3\FLOW3\I18n\Utility::parseAcceptLanguageHeader($acceptLanguageHeader);

		if ($acceptableLanguages === FALSE) {
			return $this->localizationService->getDefaultLocale();
		}

		foreach ($acceptableLanguages as $languageIdentifier) {
			if ($languageIdentifier === '*') {
				return $this->localizationService->getDefaultLocale();
			}

			try {
				$locale = new \TYPO3\FLOW3\I18n\Locale($languageIdentifier);
			} catch (\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
				continue;
			}

			$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

			if ($bestMatchingLocale !== NULL) {
				return $bestMatchingLocale;
			}
		}

		return $this->localizationService->getDefaultLocale();
	}

	/**
	 * Returns best-matching Locale object based on the locale identifier
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param string $localeIdentifier The locale identifier as used in Locale class
	 * @return \TYPO3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromLocaleTag($localeIdentifier) {
		try {
			return $this->detectLocaleFromTemplateLocale(new \TYPO3\FLOW3\I18n\Locale($localeIdentifier));
		} catch (\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $e) {
			return $this->localizationService->getDefaultLocale();
		}
	}

	/**
	 * Returns best-matching Locale object based on the template Locale object
	 * provided as parameter. System default locale will be returned if no
	 * successful matches were done.
	 *
	 * @param \TYPO3\FLOW3\I18n\Locale $locale The template Locale object
	 * @return \TYPO3\FLOW3\I18n\Locale Best-matching existing Locale instance
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @api
	 */
	public function detectLocaleFromTemplateLocale(\TYPO3\FLOW3\I18n\Locale $locale) {
		$bestMatchingLocale = $this->localeCollection->findBestMatchingLocale($locale);

		if ($bestMatchingLocale !== NULL) {
			return $bestMatchingLocale;
		}

		return $this->localizationService->getDefaultLocale();
	}
}

?>