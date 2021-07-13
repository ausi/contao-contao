<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Intl;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries as SymfonyCountries;
use Symfony\Contracts\Translation\TranslatorInterface;

class Countries
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array<string>
     */
    private $countries;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack, ContaoFramework $contaoFramework, array $availableCountries, string $defaultLocale, array $countriesList)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->contaoFramework = $contaoFramework;
        $this->defaultLocale = $defaultLocale;
        $this->countries = $this->filterCountries($availableCountries, $countriesList);
    }

    /**
     * @return array<string,string> Translated country names indexed by their uppercase ISO 3166-1 alpha-2 code
     */
    public function getCountries(string $displayLocale = null): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        $countries = [];

        foreach ($this->countries as $countryCode) {
            $langKey = 'CNT.'.strtolower($countryCode);

            if (
                $langKey !== ($label = $this->translator->trans($langKey, [], 'contao_countries', $displayLocale))
                && \is_string($label)
                && '' !== $label
            ) {
                $countries[$countryCode] = $label;
            } else {
                $countries[$countryCode] = \Locale::getDisplayRegion('_'.$countryCode, $displayLocale ?? $this->defaultLocale);
            }
        }

        (new \Collator($displayLocale ?? $this->defaultLocale))->asort($countries);

        if (!empty($GLOBALS['TL_HOOKS']['getCountries'])) {
            return $this->applyLegacyHook($countries);
        }

        return $countries;
    }

    /**
     * @return array<string> Uppercase ISO 3166-1 alpha-2 codes
     */
    public function getCountryCodes(): array
    {
        // If the legacy hook is used, it might add or remove countries
        if (!empty($GLOBALS['TL_HOOKS']['getCountries'])) {
            $countryCodes = array_keys($this->getCountries());
            sort($countryCodes);

            return $countryCodes;
        }

        return $this->countries;
    }

    /**
     * Add, remove or replace countries as configured in the container configuration.
     */
    private function filterCountries(array $countries, array $filter): array
    {
        $newList = array_filter(
            $filter,
            static function ($country) {
                return !\in_array($country[0], ['-', '+'], true);
            }
        );

        if ($newList) {
            $countries = $newList;
        }

        foreach ($filter as $country) {
            $prefix = $country[0];
            $countryCode = substr($country, 1);

            if ('-' === $prefix && \in_array($countryCode, $countries, true)) {
                unset($countries[array_search($countryCode, $countries, true)]);
            } elseif ('+' === $prefix && !\in_array($countryCode, $countries, true)) {
                $countries[] = $countryCode;
            }
        }

        sort($countries);

        return $countries;
    }

    private function applyLegacyHook(array $return)
    {
        trigger_deprecation('contao/core-bundle', '4.12', 'Using the "getCountries" hook has been deprecated and will no longer work in Contao 5.0. Decorate the %s service instead.', __CLASS__);

        $countries = SymfonyCountries::getNames('en');

        // The legacy hook works with lower case country codes
        $return = array_combine(array_map('strtolower', array_keys($return)), $return);
        $countries = array_combine(array_map('strtolower', array_keys($countries)), $countries);

        foreach ($GLOBALS['TL_HOOKS']['getCountries'] as $callback) {
            $this->contaoFramework->getAdapter(System::class)->importStatic($callback[0])->{$callback[1]}($return, $countries);
        }

        return array_combine(array_map('strtoupper', array_keys($return)), $return);
    }
}
