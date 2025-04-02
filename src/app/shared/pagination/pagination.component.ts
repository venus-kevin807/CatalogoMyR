// pagination.component.ts
import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';

@Component({
  selector: 'app-pagination',
  templateUrl: './pagination.component.html',
  styleUrl: './pagination.component.css'
})
export class PaginationComponent implements OnChanges {
  @Input() totalItems: number = 0;
  @Input() itemsPerPage: number = 10;
  @Input() currentPage: number = 1;
  @Output() pageChange = new EventEmitter<number>();

  totalPages: number = 1;
  visiblePageNumbers: number[] = [];

  ngOnChanges(changes: SimpleChanges): void {
    this.calculateTotalPages();
    this.generateVisiblePageNumbers();
  }

  calculateTotalPages(): void {
    this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
    if (this.totalPages <= 0) {
      this.totalPages = 1;
    }
  }

  generateVisiblePageNumbers(): void {
    const maxVisiblePages = 5;
    this.visiblePageNumbers = [];

    if (this.totalPages <= maxVisiblePages) {
      // Show all pages if there are 5 or fewer
      for (let i = 1; i <= this.totalPages; i++) {
        this.visiblePageNumbers.push(i);
      }
    } else {
      // For current page near start
      if (this.currentPage <= 3) {
        for (let i = 1; i <= 5; i++) {
          this.visiblePageNumbers.push(i);
        }
      }
      // For current page near end
      else if (this.currentPage >= this.totalPages - 2) {
        for (let i = this.totalPages - 4; i <= this.totalPages; i++) {
          this.visiblePageNumbers.push(i);
        }
      }
      // For current page in middle
      else {
        for (let i = this.currentPage - 2; i <= this.currentPage + 2; i++) {
          this.visiblePageNumbers.push(i);
        }
      }
    }
  }

  onPageChange(page: number): void {
    if (page !== this.currentPage && page >= 1 && page <= this.totalPages) {
      this.pageChange.emit(page);
    }
  }
}
