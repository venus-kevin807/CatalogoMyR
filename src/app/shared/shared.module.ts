import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SidebarComponent } from './sidebar/components/sidebar.component';
import { HeaderComponent } from './header/header.component';
import { FooterComponent } from './footer/footer.component';
import { PaginationComponent } from './pagination/pagination.component';
import { RouterModule } from '@angular/router';
import { BannerComponent } from './banner/banner.component';
import { BrowserModule } from '@angular/platform-browser';

@NgModule({
  declarations: [
    HeaderComponent,
    FooterComponent,
    PaginationComponent,
    BannerComponent
  ],
  imports: [
    CommonModule,
    BrowserModule,
    RouterModule
/*     RouterModule.forRoot([
      { path: 'toyota', component: AppComponent },
      { path: 'mitsubishi-caterpillar', component: AppComponent },
      { path: 'heli', component: AppComponent },
      { path: 'hangcha', component: AppComponent },
      { path: 'nissan', component: AppComponent },
      { path: 'tailift', component: AppComponent },
      { path: 'impco', component: AppComponent },
      { path: 'cascade', component: AppComponent },
      { path: 'yale', component: AppComponent },
      { path: 'maximal', component: AppComponent },
      { path: '', redirectTo: '/', pathMatch: 'full' }
    ]) */
  ],
  exports: [
    HeaderComponent,
    FooterComponent,
    PaginationComponent,
    BannerComponent
  ]
})
export class SharedModule { }
