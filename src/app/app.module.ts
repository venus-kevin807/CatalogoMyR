import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { CatalogModule } from './catalog/catalog.module';
import { SharedModule } from './shared/shared.module';
import { SidebarModule } from "./shared/sidebar/sidebar.module";
import { ProductDetailComponent } from './catalog/product-detail/product-detail.component';
import { ToastrModule } from 'ngx-toastr';

@NgModule({
  declarations: [
    AppComponent,
    ProductDetailComponent
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    ToastrModule.forRoot({
      timeOut: 5000,  // Duración en milisegundos (5 segundos en este ejemplo)
      positionClass: 'toast-bottom-right',
      preventDuplicates: true
    }),
    AppRoutingModule,
    CatalogModule,
    SharedModule,
    SidebarModule,

],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
