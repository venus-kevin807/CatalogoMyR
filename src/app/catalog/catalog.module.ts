// catalog.module.ts
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CatalogComponent } from './catalog.component';
import { ProductListComponent } from './product-list/product-list.component';

@NgModule({
  declarations: [
    CatalogComponent,
    ProductListComponent
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule
  ],
  exports: [
    CatalogComponent
  ]
})
export class CatalogModule { }
