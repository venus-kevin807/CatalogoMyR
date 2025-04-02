import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { CatalogComponent } from './catalog/catalog.component';
import { ProductDetailComponent } from './catalog/product-detail/product-detail.component';

const routes: Routes = [
  { path: '', component: CatalogComponent }, // Muestra el catálogo por defecto
  { path: 'catalog', component: CatalogComponent },
  { path: 'product/:id', component: ProductDetailComponent },
  { path: '**', redirectTo: '', pathMatch: 'full' } // Redirige rutas inválidas al inicio
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
