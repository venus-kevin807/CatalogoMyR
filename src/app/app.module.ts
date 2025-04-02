import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';

import { CatalogModule } from './catalog/catalog.module';
import { SharedModule } from './shared/shared.module';
import { SidebarModule } from "./shared/sidebar/sidebar.module";
import { ProductDetailComponent } from './catalog/product-detail/product-detail.component';

@NgModule({
  declarations: [
    AppComponent,
    ProductDetailComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    CatalogModule,
    SharedModule,
    SidebarModule,

],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
