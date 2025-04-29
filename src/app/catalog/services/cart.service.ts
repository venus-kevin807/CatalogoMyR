import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Product } from '../../shared/models/product.model';
import { ToastrService } from 'ngx-toastr';

export interface CartItem {
  product: Product;
  quantity: number;
}

@Injectable({
  providedIn: 'root'
})
export class CartService {
  private cartItemsSubject: BehaviorSubject<CartItem[]> = new BehaviorSubject<CartItem[]>([]);
  public cartItems$: Observable<CartItem[]> = this.cartItemsSubject.asObservable();
  private cartCountSubject: BehaviorSubject<number> = new BehaviorSubject<number>(0);
  public cartCount$: Observable<number> = this.cartCountSubject.asObservable();

  constructor(private toastr: ToastrService) {
    this.loadCartFromStorage();
  }

  private loadCartFromStorage(): void {
    const storedCart = localStorage.getItem('cart');
    if (storedCart) {
      const cartItems = JSON.parse(storedCart);
      this.cartItemsSubject.next(cartItems);
      this.updateCartCount();
    }
  }

  private saveCartToStorage(): void {
    localStorage.setItem('cart', JSON.stringify(this.cartItemsSubject.value));
  }

  private updateCartCount(): void {
    const count = this.cartItemsSubject.value.reduce((total, item) => total + item.quantity, 0);
    this.cartCountSubject.next(count);
  }

  addToCart(product: Product): void {
    const currentItems = this.cartItemsSubject.value;
    const existingItemIndex = currentItems.findIndex(item => item.product.id_repuesto === product.id_repuesto);

    if (existingItemIndex !== -1) {
      const updatedItems = [...currentItems];
      updatedItems[existingItemIndex].quantity += 1;
      this.cartItemsSubject.next(updatedItems);
    } else {
      const updatedItems = [...currentItems, { product, quantity: 1 }];
      this.cartItemsSubject.next(updatedItems);
    }

    this.updateCartCount();
    this.saveCartToStorage();
    this.toastr.success(`${product.nombre} agregado al carrito`, 'Producto agregado');
  }

  removeFromCart(productId: number): void {
    const product = this.cartItemsSubject.value.find(item => item.product.id_repuesto === productId)?.product;
    const updatedItems = this.cartItemsSubject.value.filter(
      item => item.product.id_repuesto !== productId
    );
    this.cartItemsSubject.next(updatedItems);
    this.updateCartCount();
    this.saveCartToStorage();

    if (product) {
      this.toastr.info(`${product.nombre} eliminado del carrito`, 'Producto eliminado');
    }
  }

  updateQuantity(productId: number, quantity: number): void {
    if (quantity <= 0) {
      this.removeFromCart(productId);
      return;
    }

    const currentItems = this.cartItemsSubject.value;
    const itemIndex = currentItems.findIndex(item => item.product.id_repuesto === productId);

    if (itemIndex !== -1) {
      const updatedItems = [...currentItems];
      updatedItems[itemIndex].quantity = quantity;
      this.cartItemsSubject.next(updatedItems);
      this.updateCartCount();
      this.saveCartToStorage();

      this.toastr.info('Cantidad actualizada', 'Carrito actualizado');
    }
  }

  clearCart(): void {
    this.cartItemsSubject.next([]);
    this.cartCountSubject.next(0);
    localStorage.removeItem('cart');

    this.toastr.warning('Carrito vaciado', 'Carrito');
  }

  getCartTotal(): number {
    return this.cartItemsSubject.value.reduce(
      (total, item) => total + (item.product.precio * item.quantity),
      0
    );
  }
}
