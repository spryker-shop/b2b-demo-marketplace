import { ApplicationConfig } from '@angular/core';
import { provideHttpClient } from '@angular/common/http';
import { provideTranslateService } from '@ngx-translate/core';
import { provideTranslateHttpLoader } from '@ngx-translate/http-loader';
import { ASSETS } from '../services/configurator.service';

export const appConfig: ApplicationConfig = {
    providers: [
        provideHttpClient(),
        provideTranslateService({
            fallbackLang: 'en_US',
        }),
        provideTranslateHttpLoader({
            prefix: `${ASSETS}/assets/i18n/`,
            suffix: '.json',
        }),
    ],
};
