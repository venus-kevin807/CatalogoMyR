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
  cartItems: CartItem[] = [];
  cartCount: number = 0;
  isCartOpen: boolean = false;
  cartTotal: number = 0;
  isMobile: boolean = window.innerWidth <= 768;

  private subscriptions: Subscription[] = [];

  constructor(
    private sidebarService: SidebarService,
    private router: Router,
    private cartService: CartService
  ) {}

  ngOnInit(): void {
    this.loadManufacturers();
    this.loadCartData();
    this.subscriptions.push(
      this.sidebarService.filtersCleared$.subscribe(cleared => {
        if (cleared) {
          this.selectedManufacturer = null;
        }
      })
    );
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    const cartDropdown = document.querySelector('.cart-dropdown');
    const cartIcon = document.querySelector('.cart-icon');

    if (this.isCartOpen && cartDropdown && cartIcon) {
      const clickedInside = cartDropdown.contains(event.target as Node) || cartIcon.contains(event.target as Node);

      if (!clickedInside) {
        this.isCartOpen = false;
      }
    }
  }

  onHamburgerClick(): void {
    this.sidebarService.toggleSidebar();
  }

  @HostListener('window:resize', ['$event'])
  onResize(event: any): void {
    this.isMobile = window.innerWidth <= 768;
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
    this.router.navigate(['/catalog']);
    this.sidebarService.selectManufacturer(manufacturer.id);
    this.selectedManufacturer = manufacturer;
  }

  loadCartData(): void {
    this.subscriptions.push(
      this.cartService.cartItems$.subscribe(items => {
        this.cartItems = items;
        this.cartTotal = this.cartService.getCartTotal();
      })
    );
    this.subscriptions.push(
      this.cartService.cartCount$.subscribe(count => {
        this.cartCount = count;
      })
    );
  }

  toggleCartDropdown(event?: MouseEvent): void {
    if (event) {
      event.stopPropagation();
    }
    this.isCartOpen = !this.isCartOpen;
  }

  onCartClick(event: MouseEvent): void {
    event.stopPropagation();
  }

  decreaseQuantity(productId: number): void {
    const item = this.cartItems.find(item => item.product.id_repuesto === productId);
    if (item && item.quantity > 1) {
      this.cartService.updateQuantity(productId, item.quantity - 1);
    }
  }

  increaseQuantity(productId: number): void {
    const item = this.cartItems.find(item => item.product.id_repuesto === productId);
    if (item) {
      this.cartService.updateQuantity(productId, item.quantity + 1);
    }
  }

  removeItem(productId: number): void {
    this.cartService.removeFromCart(productId);
  }

  clearCart(): void {
    this.cartService.clearCart();
  }

  checkout(): void {
    this.router.navigate(['/checkout']);


    const message = `Hola, me interesa comprar los siguientes productos:
    ${this.cartItems.map(item => `${item.quantity}x ${item.product.nombre} (Ref: ${item.product.str_referencia})`).join('\n')}
    Total: $${this.cartTotal.toLocaleString()}`;

    const whatsappUrl = `https://wa.me/3176465312?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
    this.isCartOpen = false;
  }
}
