import { Component } from "vue";
import { z } from "zod";

export interface ICategory {
   name: string;
   slug: string;
   id: string;
   image: string;
   parent_id: number | null;
   parameters: IParameter[];
   children: ICategory[];
}

export interface ICategoryParameter {
   id: number;
   parameter_id: number;
   category_id: number;
   is_required: boolean;
   sort_order: number;
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

export interface IParameterOption {
   id: number;
   value: string;
}

export interface IParameter {
   id: number;
   placeholder: string;
   component: string;
   type: string;
   pivot: ICategoryParameter;
   unit: string | null;
   options: IParameterOption[];
}

export interface IProduct {
   id: number;
   title: string;
   description: string;
   phone: string | null;
   category_id: number;
   user_id: number;
}

export interface IParameter {
   id: number;
   placeholder: string;
   type: string;
   pivot: ICategoryParameter;
   unit: string | null;
   options: IParameterOption[];
}
