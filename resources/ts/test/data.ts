interface Product {
   title: string;
   subtitle: string;
   price: string;
   image: string;
}

export const products: Product[] = [
   {
      title: "Macbook Pro 16",
      subtitle: "M2 Max, 32GB RAM, 1TB SSD",
      price: "$2 499",
      image: "./images/macbook.webp",
   },
   {
      title: "iPhone 15 Pro",
      subtitle: "Titanium, 256GB",
      price: "$1 199",
      image: "https://primefaces.org/cdn/primevue/images/product/black-watch.jpg",
   },
   {
      title: "Samsung Galaxy S24",
      subtitle: "Ultra 5G, 512GB",
      price: "$1 299",
      image: "https://primefaces.org/cdn/primevue/images/product/blue-t-shirt.jpg",
   },
   {
      title: "Sony PlayStation 5",
      subtitle: "Digital Edition",
      price: "$450",
      image: "https://primefaces.org/cdn/primevue/images/product/gaming-set.jpg",
   },
   {
      title: "Canon EOS R5",
      subtitle: "Body Only, 45MP",
      price: "$3 899",
      image: "https://primefaces.org/cdn/primevue/images/product/green-earbuds.jpg",
   },
];

export const responsiveOptions = [
   {
      breakpoint: "2560px",
      numVisible: 8,
      numScroll: 4,
   },
   {
      breakpoint: "1400px",
      numVisible: 5,
      numScroll: 1,
   },
   {
      breakpoint: "1199px",
      numVisible: 5,
      numScroll: 1,
   },
   {
      breakpoint: "767px",
      numVisible: 3.5,
      numScroll: 1,
   },
   {
      breakpoint: "575px",
      numVisible: 2.2,
      numScroll: 1,
   },
];
