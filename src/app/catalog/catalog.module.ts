// catalog.module.ts
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CatalogComponent } from './catalog.component';
import { ProductListComponent } from './product-list/product-list.component';
import { SharedModule } from "../shared/shared.module";

@NgModule({
  declarations: [
    CatalogComponent,
    ProductListComponent
  ],
  imports: [
    CommonModule,
    RouterModule,
    FormsModule,
    SharedModule
],
  exports: [
    CatalogComponent
  ]
})
export class CatalogModule { }
