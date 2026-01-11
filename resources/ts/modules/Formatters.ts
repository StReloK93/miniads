import { TreeNode } from "primevue/treenode";

export function formatCategories(categories): TreeNode[] {
   return categories.map((item) => {
      const node: TreeNode = {
         key: item.id, // Key doim String bo'lishi kerak
         label: item.name, // Sizdagi 'name' yoki 'title'
         parent_id: item.parent_id, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         image: item.image, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         is_page: item.is_page, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         children: [], // Bolalar uchun bo'sh massiv
         droppable: !item.is_page, // Bolalar uchun bo'sh massiv
      };
      if (item.children && item.children.length > 0) {
         node.children = formatCategories(item.children);
      }
      return node;
   });
}
