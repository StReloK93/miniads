export interface ICategory {
   name: string;
   slug: string;
   id: string;
   image: string;
   children: ICategory[];
}
