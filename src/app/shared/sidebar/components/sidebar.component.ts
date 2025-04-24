import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subscription } from 'rxjs';
import { SidebarService } from '../services/sidebar.service';
import { Category } from '../models/sidebar.model';
import { Manufacturer } from '../../models/manufacturer.model';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit, OnDestroy {
  isSidebarOpen: boolean = false; // Estado del drawer móvil
  loading: boolean = false;
  error: string = '';

  categories: Category[] = [];
  manufacturers: Manufacturer[] = [];

  private sidebarSub: Subscription = new Subscription();

  // Variables para modales (ejemplo)
  isTermsModalOpen: boolean = false;
  isAboutUsOpen: boolean = false;

  constructor(private sidebarService: SidebarService) {}

  ngOnInit(): void {
    // Suscribirse al estado del sidebar móvil
    this.sidebarSub = this.sidebarService.sidebarOpen$.subscribe(open => {
      this.isSidebarOpen = open;
    });

    // Cargar datos
    this.loadCategories();
    this.loadManufacturers();
  }

  ngOnDestroy(): void {
    this.sidebarSub.unsubscribe();
  }

  loadCategories(): void {
    this.loading = true;
    this.sidebarService.getCategories().subscribe({
      next: (cats) => {
        this.categories = cats;
        this.loading = false;
      },
      error: (err) => {
        this.error = err.message;
        this.loading = false;
      }
    });
  }

  loadManufacturers(): void {
    this.sidebarService.getManufacturers().subscribe({
      next: (mans) => {
        this.manufacturers = mans;
      },
      error: (err) => {
        console.error(err);
      }
    });
  }

  // Alterna la visualización de subcategorías
  toggleSubcategories(category: any): void {
    category.showSubcategories = !category.showSubcategories;
  }

  // Selecciona una subcategoría y cierra el drawer en móvil
  selectSubcategory(categoryId: number, subcategoryId: number, subcategoryName: string): void {
    this.sidebarService.selectSubcategory(categoryId, subcategoryId, subcategoryName);
    this.sidebarService.toggleSidebar(false); // Cierra para móviles
  }

  clearFilters(): void {
    this.sidebarService.clearFilters();
    this.sidebarService.toggleSidebar(false);
  }

  selectManufacturer(manufacturerId: number): void {
    this.sidebarService.selectManufacturer(manufacturerId);
    this.sidebarService.toggleSidebar(false);
  }

  openTermsModal(event: Event): void {
    event.preventDefault();
    this.isTermsModalOpen = true;
  }
  closeTermsModal(): void {
    this.isTermsModalOpen = false;
  }
  openAboutUs(event: Event): void {
    event.preventDefault();
    this.isAboutUsOpen = true;
  }
  closeAboutUs(): void {
    this.isAboutUsOpen = false;
  }

  // Cierra el drawer móvil
  closeSidebar(): void {
    this.sidebarService.toggleSidebar(false);
  }

  retryLoading(): void {
    this.loadCategories();
  }
}
