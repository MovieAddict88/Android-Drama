package short.drama.ronald.com.adapter;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions;

import short.drama.ronald.com.R;
import short.drama.ronald.com.model.Drama;

import java.util.ArrayList;
import java.util.List;

public class DramaAdapter extends RecyclerView.Adapter<DramaAdapter.ViewHolder> {

    public interface OnItemClickListener {
        void onItemClick(Drama drama);
    }

    private final List<Drama> items = new ArrayList<>();
    private OnItemClickListener listener;
    private final Context context;

    public DramaAdapter(Context context) {
        this.context = context;
    }

    public void setOnItemClickListener(OnItemClickListener listener) {
        this.listener = listener;
    }

    public void setData(List<Drama> dramas) {
        items.clear();
        if (dramas != null) items.addAll(dramas);
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_drama, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Drama drama = items.get(position);
        holder.tvTitle.setText(drama.getBookName());
        holder.tvEpisodes.setText(drama.getChapterCount() + " Episodes");

        if (drama.getRankVo() != null && drama.getRankVo().getHotCode() != null) {
            holder.tvHot.setVisibility(View.VISIBLE);
            holder.tvHot.setText(drama.getRankVo().getHotCode());
        } else {
            holder.tvHot.setVisibility(View.GONE);
        }

        Glide.with(context)
                .load(drama.getCoverUrl())
                .placeholder(R.drawable.ic_placeholder)
                .error(R.drawable.ic_placeholder)
                .transition(DrawableTransitionOptions.withCrossFade())
                .into(holder.ivCover);

        holder.itemView.setOnClickListener(v -> {
            if (listener != null) listener.onItemClick(drama);
        });
    }

    @Override
    public int getItemCount() {
        return items.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView ivCover;
        TextView tvTitle;
        TextView tvEpisodes;
        TextView tvHot;

        ViewHolder(@NonNull View itemView) {
            super(itemView);
            ivCover = itemView.findViewById(R.id.iv_cover);
            tvTitle = itemView.findViewById(R.id.tv_title);
            tvEpisodes = itemView.findViewById(R.id.tv_episodes);
            tvHot = itemView.findViewById(R.id.tv_hot);
        }
    }
}
