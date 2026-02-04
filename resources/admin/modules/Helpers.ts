import { TreeNode } from "primevue/treenode";

export function findParentId(tree: TreeNode[], dragNodeKey: string): TreeNode | null {
   for (let index = 0; index < tree.length; index++) {
      if (tree[index].key === dragNodeKey) return null;
   }

   // 2. Ichki darajalardan qidiramiz
   return searchInside(tree, dragNodeKey);
}

function searchInside(list: TreeNode[], targetKey: string): TreeNode | null {
   for (let index = 0; index < list.length; index++) {
      const parentNode = list[index];

      if (parentNode.children && parentNode.children.length > 0) {
         // Bevosita shu tugunning bolalari orasidami?
         for (let j = 0; j < parentNode.children.length; j++) {
            if (parentNode.children[j].key == targetKey) return parentNode;
         }

         const foundId = searchInside(parentNode.children, targetKey);
         if (foundId !== null) return foundId;
      }
   }

   return null;
}

export function formatCategories(categories): TreeNode[] {
   if (!categories || categories.length === 0) return [];

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
