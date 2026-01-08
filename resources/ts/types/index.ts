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
   props: IProps | null;
   generateProps?: () => Promise<void>;
   name: string;
   placeholder?: string;
   schema: z.ZodTypeAny;
   class?: string[];
   value?: string | number | boolean | null | undefined;
   selectOption?: IProps;
   condition?: ($form: any) => boolean;
}

export interface IProps {
   [key: string]: unknown;
}
