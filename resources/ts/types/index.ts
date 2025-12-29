import { Component } from "vue";
import { z } from "zod";

export interface ICategory {
   name: string;
   slug: string;
   id: string;
   image: string;
   children: ICategory[];
}

export interface InputConfig {
   component: Component;
   props: {
      name: string;
      [key: string]: unknown;
   };
   schema: z.ZodTypeAny;
}
