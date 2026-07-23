import { DeleteButton } from "@/Components/admin/DeleteButton";

export function DeleteArticleButton({ id, title }: { id: number; title: string }) {
  return (
    <DeleteButton itemLabel={`Artikel "${title}"`} routeName="admin.articles.destroy" routeParams={id} />
  );
}
