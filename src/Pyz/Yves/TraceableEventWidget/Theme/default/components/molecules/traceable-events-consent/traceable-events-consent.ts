import TraceableEventsConsentCore from 'TraceableEventWidget/components/molecules/traceable-events-consent/traceable-events-consent';
import {
    CONSENT_PURPOSE_MARKETING,
    CONSENT_PURPOSE_PREFERENCES,
    CONSENT_PURPOSE_STATISTICS,
} from 'TraceableEventWidget/components/molecules/traceable-events-consent/consent-purposes';

// What HubSpot's cookie banner reports; `categories` only when the banner asks for consent per category.
interface HubSpotPrivacyConsent {
    allowed: boolean;
    categories?: Partial<Record<string, boolean>>;
}

type HubSpotCommand = [string, ...unknown[]];

declare global {
    interface Window {
        // An array until the HubSpot script loads, which then runs the queued commands and every later one.
        _hsp?: { push: (...commands: HubSpotCommand[]) => unknown };
    }
}

// HubSpot's cookie categories are necessary, analytics, advertisement and functionality.
const HUBSPOT_CATEGORIES_BY_PURPOSE: Readonly<Record<string, string>> = {
    [CONSENT_PURPOSE_STATISTICS]: 'analytics',
    [CONSENT_PURPOSE_MARKETING]: 'advertisement',
    [CONSENT_PURPOSE_PREFERENCES]: 'functionality',
};

// Relates the storefront tracking to the HubSpot cookie banner, whose script is set in the Back Office head scripts.
export default class TraceableEventsConsent extends TraceableEventsConsentCore {
    protected hubSpotConsent: HubSpotPrivacyConsent | null = null;

    // HubSpot calls the listener with the stored consent once its banner loads, and again on every change.
    // Until then nothing is granted, e.g. when a content blocker stops HubSpot.
    protected init(): void {
        window._hsp = window._hsp || [];
        window._hsp.push([
            'addPrivacyConsentListener',
            (consent: HubSpotPrivacyConsent): void => {
                this.hubSpotConsent = consent;
                this.notifyConsentChange();
            },
        ]);
    }

    // A banner without categories asks for one consent that covers all of them.
    protected isCategoryGranted(category: string): boolean {
        if (!this.hubSpotConsent) {
            return false;
        }

        if (!this.hubSpotConsent.categories) {
            return this.hubSpotConsent.allowed === true;
        }

        return this.hubSpotConsent.categories[category] === true;
    }

    protected get categoriesByPurpose(): Readonly<Record<string, string>> {
        return HUBSPOT_CATEGORIES_BY_PURPOSE;
    }
}
