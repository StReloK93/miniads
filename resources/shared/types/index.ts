import { Component } from "vue";
import { z } from "zod";

export interface ICategory {
   name: string;
   slug: string;
   id: number;
   image: string;
   products: IProduct[];
   is_page: boolean;
   parent: ICategory;
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
   value?: any;
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
   price: number | null;
   phone: string | null;
   category_id: number;
   parameter_values: IParameterValue[];
   images: { id: number; product_id: number; src: string }[];
}

export interface IParameterValue {
   id: number;
   parameter: IParameter;
   parameter_id: number;
   product_id: number;
   value: string;
}

export interface IParameter {
   id: number;
   placeholder: string;
   type: string;
   pivot: ICategoryParameter;
   unit: string | null;
   options: IParameterOption[];
}

export interface ITreeNode {
   id: number;
   title: string;
   children?: ITreeNode[];
   parent_id?: number | null;
   image?: string;
   is_page?: boolean;
   draggable?: boolean; // o‘zi ko‘chiriladimi
   droppable?: boolean; // ichiga tashlash mumkinmi
}
