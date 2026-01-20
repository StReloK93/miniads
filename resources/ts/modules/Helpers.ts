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

export const preloadImages = (urls: string[]) => {
   const promises = urls.map((url) => {
      return new Promise((resolve) => {
         const img = new Image();
         img.src = url;
         img.onload = resolve; // Rasm muvaffaqiyatli yuklansa
         img.onerror = resolve; // Xatolik bo'lsa ham loading to'xtab qolmasligi uchun
      });
   });
   return Promise.all(promises);
};
