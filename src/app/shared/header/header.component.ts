import { Component, OnInit, OnDestroy, HostListener } from '@angular/core';
import { Router } from '@angular/router';
import { SidebarService } from '../sidebar/services/sidebar.service';
import { Manufacturer } from '../models/manufacturer.model';
import { CartService, CartItem } from '../../catalog/services/cart.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css']
})
export class HeaderComponent implements OnInit, OnDestroy {
  isSidebarOpen = false;
  manufacturers: Manufacturer[] = [];
  selectedManufacturer: Manufacturer | null = null;

  // Propiedades del carrito
  cartItems: CartItem[] = [];
  cartCount: number = 0;
  isCartOpen: boolean = false;
  cartTotal: number = 0;

  private subscriptions: Subscription[] = [];

  constructor(
    private sidebarService: SidebarService,
    private router: Router,
    private cartService: CartService
  ) {}

  ngOnInit(): void {
    this.loadManufacturers();
    this.loadCartData();

    // Agregar suscripción a la notificación de limpieza de filtros
    this.subscriptions.push(
      this.sidebarService.filtersCleared$.subscribe(cleared => {
        if (cleared) {
          this.selectedManufacturer = null;
        }
      })
    );
  }

  ngOnDestroy(): void {
    // Cancelar todas las suscripciones para evitar memory leaks
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  // Método para cerrar el carrito cuando se hace clic fuera de él
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    // Verificar si el clic fue dentro del carrito o en el ícono del carrito
    const cartDropdown = document.querySelector('.cart-dropdown');
    const cartIcon = document.querySelector('.cart-icon');

    if (this.isCartOpen && cartDropdown && cartIcon) {
      const clickedInside = cartDropdown.contains(event.target as Node) || cartIcon.contains(event.target as Node);

      if (!clickedInside) {
        this.isCartOpen = false;
      }
    }
  }

  loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe({
      next: (manufacturers) => {
        this.manufacturers = manufacturers;
      },
      error: (err) => {
        console.error('Error loading manufacturers:', err);
      }
    });
  }

  selectManufacturer(manufacturer: Manufacturer): void {
    // Navigate to catalog
    this.router.navigate(['/catalog']);

    // Select manufacturer in sidebar service
    this.sidebarService.selectManufacturer(manufacturer.id);

    // Update selected manufacturer
    this.selectedManufacturer = manufacturer;
  }

  // Método para cargar datos del carrito
  loadCartData(): void {
    // Suscribirse a los items del carrito
    this.subscriptions.push(
      this.cartService.cartItems$.subscribe(items => {
        this.cartItems = items;
        this.cartTotal = this.cartService.getCartTotal();
      })
    );

    // Suscribirse al contador del carrito
    this.subscriptions.push(
      this.cartService.cartCount$.subscribe(count => {
        this.cartCount = count;
      })
    );
  }

  // Método para mostrar/ocultar el dropdown del carrito
  toggleCartDropdown(event?: MouseEvent): void {
    // Si se proporciona un evento, evitar propagación
    if (event) {
      event.stopPropagation();
    }
    this.isCartOpen = !this.isCartOpen;
  }

  // Evitar que los clics dentro del carrito lo cierren
  onCartClick(event: MouseEvent): void {
    event.stopPropagation();
  }

  // Método para disminuir la cantidad de un producto
  decreaseQuantity(productId: number): void {
    const item = this.cartItems.find(item => item.product.id_repuesto === productId);
    if (item && item.quantity > 1) {
      this.cartService.updateQuantity(productId, item.quantity - 1);
    }
  }

  // Método para aumentar la cantidad de un producto
  increaseQuantity(productId: number): void {
    const item = this.cartItems.find(item => item.product.id_repuesto === productId);
    if (item) {
      this.cartService.updateQuantity(productId, item.quantity + 1);
    }
  }

  // Método para eliminar un producto del carrito
  removeItem(productId: number): void {
    this.cartService.removeFromCart(productId);
  }

  // Método para vaciar todo el carrito
  clearCart(): void {
    this.cartService.clearCart();
  }

  // Método para proceder al checkout
  checkout(): void {
    // Aquí puedes implementar la lógica para finalizar la compra
    // Por ejemplo, navegar a una página de checkout
    this.router.navigate(['/checkout']);


    const message = `Hola, me interesa comprar los siguientes productos:
    ${this.cartItems.map(item => `${item.quantity}x ${item.product.nombre} (Ref: ${item.product.str_referencia})`).join('\n')}
    Total: $${this.cartTotal.toLocaleString()}`;

    const whatsappUrl = `https://wa.me/3176465312?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');

    // Cerrar el dropdown después del checkout
    this.isCartOpen = false;
  }
}
