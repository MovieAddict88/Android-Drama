package short.drama.ronald.com.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import short.drama.ronald.com.R;

import java.util.List;

public class EpisodeListAdapter extends RecyclerView.Adapter<EpisodeListAdapter.ViewHolder> {

    public interface OnItemClickListener {
        void onItemClick(int position);
    }

    private final List<String> names;
    private int currentIndex;
    private OnItemClickListener listener;

    public EpisodeListAdapter(List<String> names, int currentIndex) {
        this.names = names;
        this.currentIndex = currentIndex;
    }

    public void setOnItemClickListener(OnItemClickListener listener) {
        this.listener = listener;
    }

    public void setCurrentIndex(int index) {
        int prev = currentIndex;
        currentIndex = index;
        if (prev >= 0) notifyItemChanged(prev);
        notifyItemChanged(index);
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_episode_list, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        holder.tvName.setText(names.get(position));
        if (position == currentIndex) {
            holder.itemView.setBackgroundResource(R.drawable.bg_episode_active);
        } else {
            holder.itemView.setBackgroundResource(android.R.color.transparent);
        }
        holder.itemView.setOnClickListener(v -> {
            if (listener != null) listener.onItemClick(position);
        });
    }

    @Override
    public int getItemCount() {
        return names != null ? names.size() : 0;
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName;

        ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvName = itemView.findViewById(R.id.tv_name);
        }
    }
}
