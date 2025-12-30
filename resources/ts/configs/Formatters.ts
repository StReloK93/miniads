export const formatCategories = (categories) => {
   return categories.map((item) => {
      const node = {
         key: item.id, // Key doim String bo'lishi kerak
         label: item.name, // Sizdagi 'name' yoki 'title'
         data: item, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         image: item.image, // Ob'ektning hamma ma'lumotini saqlab qo'yish foydali
         children: [], // Bolalar uchun bo'sh massiv
      };
      if (item.children && item.children.length > 0) {
         node.children = formatCategories(item.children);
      }
      return node;
   });
};
