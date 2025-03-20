// header.component.ts
import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';


@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.css'],

})
export class HeaderComponent {
  navItems = [
    { name: 'TOYOTA', link: '/toyota' },
    { name: 'MITSUBISHI/CATERPILLAR', link: '/mitsubishi-caterpillar' },
    { name: 'HELI', link: '/heli' },
    { name: 'HANGCHA', link: '/hangcha' },
    { name: 'NISSAN', link: '/nissan' },
    { name: 'TAILIFT', link: '/tailift' },
    { name: 'IMPCO', link: '/impco' },
    { name: 'CASCADE', link: '/cascade' },
    { name: 'YALE', link: '/yale' },
    { name: 'MAXIMAL', link: '/maximal' }
  ];
}
