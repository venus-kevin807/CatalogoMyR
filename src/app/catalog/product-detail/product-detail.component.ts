import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ProductService } from './../services/product.service';
import { Product } from '../../shared/models/product.model';

@Component({
  selector: 'app-product-detail',
  templateUrl: './product-detail.component.html',
  styleUrls: ['./product-detail.component.css']
})
export class ProductDetailComponent implements OnInit {
  product: Product | null = null;
  loading = true;
  error: string | null = null;
  isDescriptionExpanded = true; // Por defecto expandido


  constructor(
    private route: ActivatedRoute,
    private productService: ProductService
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const productId = +params['id']; // Convertir a número
      if (productId) {
        this.loadProductDetails(productId);
      } else {
        this.error = 'ID de producto inválido';
        this.loading = false;
      }
    });
  }

  toggleDescription(): void {
    this.isDescriptionExpanded = !this.isDescriptionExpanded;
  }

  private loadProductDetails(productId: number): void {
    this.loading = true;
    this.error = null;

    const filters = { id_repuesto: productId };
    this.productService.getProducts(filters).subscribe({
      next: (response) => {
        if (response.products.length > 0) {
          this.product = response.products[0];
        } else {
          this.error = 'Producto no encontrado';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error cargando detalles del producto:', err);
        this.error = 'Error al cargar los detalles del producto';
        this.loading = false;
      }
    });
  }
}
