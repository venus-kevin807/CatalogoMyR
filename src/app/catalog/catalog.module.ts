import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CatalogComponent } from './catalog.component';
import { ProductListComponent } from './product-list/product-list.component';
import { SharedModule } from '../shared/shared.module'; // Importamos shared si usa algo de ahí

@NgModule({
  declarations: [
    CatalogComponent,
    ProductListComponent
  ],
  imports: [
    CommonModule,
    SharedModule
  ],
  exports: [
    CatalogComponent
  ]
})
export class CatalogModule { }
