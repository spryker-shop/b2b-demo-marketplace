import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { Observable } from 'rxjs';
import { catchError, map, shareReplay, switchMap, tap } from 'rxjs/operators';
import { environment } from '../environments/environment';
import { ProductData, ProductMetaData } from './types';

@Injectable({ providedIn: 'root' })
export class ProductService {
    constructor(private http: HttpClient, private translate: TranslateService) {
        translate.addLangs(['en_US', 'de_DE']);
        translate.setDefaultLang('en_US');
    }

    private token = this.getToken();

    private data$ = this.http
        .get<{ data: ProductData }>('/', {
            params: { getConfigurationByToken: this.token },
        })
        .pipe(
            !environment.production
                ? catchError(() => this.http.get<{ data: ProductData }>('/assets/mock.json'))
                : tap(),
            switchMap((response) => {
                if (!response) {
                    return;
                }

                return this.translate.use(response.data.locale_name).pipe(map(() => response.data));
            }),
            shareReplay({ bufferSize: 1, refCount: true }),
        );

    getData(): Observable<ProductData> {
        return this.data$;
    }

    getMetaData(data: FormData): Observable<ProductMetaData> {
        return this.http.post<ProductMetaData>('/', data, {
            params: { prepareConfiguration: this.token },
        });
    }

    private getToken(): string {
        const locationSearchArr = location.search.split('=');

        return locationSearchArr[locationSearchArr.length - 1];
    }
    /* tslint:enable */
}
