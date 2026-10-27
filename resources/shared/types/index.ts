import { Component } from "vue";
import { z } from "zod";

export interface ICategory {
   name: string;
   slug: string;
   id: number;
   image: string;
   products: IProduct[];
   is_page: boolean;
   with_price: boolean;
   with_image: boolean;
   parent: ICategory;
   parent_id: number | null;
   /** Asosiy bo'lim id'si (Uy-joy, Avto...) — server hisoblaydi */
   root_id?: number;
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
   props?: IProps | null;
   generateProps?: () => Promise<void>;
   name: string;
   placeholder?: string;
   schema: z.ZodTypeAny;
   class?: string[];
   value?: any;
   selectOption?: IProps;
   teleport_child_class?: string;
   teleport_parent_class?: string;
   required?: boolean;
   optional?: boolean;
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
   title: string;
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
   back_color: { id: number; gradient: string; border: string };
   price_type: { type: string; name: string; position: "right" | "left" };
   phone: string | null;
   category_id: number;
   views_count: number;
   category: ICategory;
   parameter_values: IParameterValue[];
   images: {
      id: number;
      product_id: number;
      src: string;
      crop_src: string | null;
      // Ro'yxat kartalari uchun kichik nusxa (yo'q bo'lsa src)
      thumb_src: string | null;
      crop_x: number;
      crop_y: number;
      crop_scale: number;
   }[];
   is_favorite: boolean;
   created_at: string;
   district: { id: number; name: string };
   district_id: number;
   updated_at: string;
   expires_at: string;
   published_at: string;
   user: IUser;
   user_id: number;
   days: {
      current: number;
      max: number;
   };
   similar_products?: IProduct[];
}

// E'lon yaratish/yangilash uchun API'ga yuboriladigan ma'lumot (multipart/form-data)
export interface IProductPayload {
   title: string;
   description?: string | null;
   phone: string | null;
   price?: number | null;
   price_type_id?: number | null;
   back_color_id?: number | null;
   category_id: number | string | undefined;
   district_id: number | null;
   parameters: { id: string | number; value: unknown }[];
   images?: unknown[];
}

export interface IUser {
   id: number;
   name: string;
   username: string | null;
   telegram_user_id: number | null;
   role: "user" | "admin";
   active_district_id: number | null;
   active_district?: IDistrict | null;
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
   draggable?: boolean; // o'zi ko'chiriladimi
   droppable?: boolean; // ichiga tashlash mumkinmi
}

export interface IDistrict {
   id: number;
   name: string;
}

export interface IPaginatedResponse<T> {
   data: T[];
   current_page: number;
   last_page: number;
   per_page: number;
   total: number;
   next_page_url: string | null;
   prev_page_url: string | null;
}
