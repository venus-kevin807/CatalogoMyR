// product-list.component.ts
import { Component, Input, Output, EventEmitter } from '@angular/core';

interface Product {
  id: number;
  name: string;
  price: number;
  image: string;
}

@Component({
  selector: 'app-product-list',
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.css']
})
export class ProductListComponent {
  @Input() products: Product[] = [];
  @Output() favoriteAdded = new EventEmitter<number>();

  addToFavorites(productId: number): void {
    this.favoriteAdded.emit(productId);
  }
}
