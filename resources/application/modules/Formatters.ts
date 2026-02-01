import { ITreeNode } from "@shared/types";

export function formatCategories(categories): ITreeNode[] {
   if (!categories || categories.length === 0) return [];

   return categories.map((item) => {
      const node: ITreeNode = {
         id: item.id, // Key doim String bo'lishi kerak
         title: item.name, // Sizdagi 'name' yoki 'title'
         parent_id: item.parent_id, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         image: item.image, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         is_page: item.is_page, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         children: [], // Bolalar uchun bo'sh massiv
         droppable: !item.is_page, // Bolalar uchun bo'sh massiv
         draggable: true, // Bolalar uchun bo'sh massiv
      };
      if (item.children && item.children.length > 0) {
         node.children = formatCategories(item.children);
      }
      return node;
   });
}
